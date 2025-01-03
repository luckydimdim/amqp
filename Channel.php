<?php

declare(strict_types=1);

namespace Typhoon\Amqp091;

use Amp\Cancellation;
use Amp\Future;
use Amp\NullCancellation;
use Typhoon\Amqp091\Internal\ChannelMode;
use Typhoon\Amqp091\Internal\ConfirmationListener;
use Typhoon\Amqp091\Internal\Consumer;
use Typhoon\Amqp091\Internal\ConsumerTagGenerator;
use Typhoon\Amqp091\Internal\Hooks;
use Typhoon\Amqp091\Internal\Io\AmqpConnection;
use Typhoon\Amqp091\Internal\MessageProperties;
use Typhoon\Amqp091\Internal\Properties;
use Typhoon\Amqp091\Internal\Protocol;
use Typhoon\Amqp091\Internal\Protocol\Frame;

/**
 * @api
 */
final class Channel
{
    private readonly Consumer $consumer;

    private readonly ConsumerTagGenerator $consumerTags;

    private readonly ConfirmationListener $confirms;

    private ChannelMode $mode = ChannelMode::regular;

    private bool $isClosed = false;

    /**
     * @param non-negative-int $channelId
     */
    public function __construct(
        private readonly int $channelId,
        private readonly AmqpConnection $connection,
        private readonly Properties $properties,
        private readonly Hooks $hooks,
    ) {
        $this->consumerTags = new ConsumerTagGenerator();
        $this->confirms = new ConfirmationListener(
            $this->hooks,
            $this->channelId,
        );
        $this->consumer = new Consumer(
            $this,
            $this->hooks,
            $this->channelId,
        );

        $this->consumer->run();
    }

    /**
     * @throws \Throwable
     */
    public function publish(
        Message $message,
        string $exchange = '',
        string $routingKey = '',
        bool $mandatory = false,
        bool $immediate = false,
    ): ?Confirmation {
        $this->connection->writeFrame((function () use ($message, $exchange, $routingKey, $mandatory, $immediate): \Generator {
            yield Protocol\Method::basicPublish(
                channelId: $this->channelId,
                exchange: $exchange,
                routingKey: $routingKey,
                mandatory: $mandatory,
                immediate: $immediate,
            );

            yield new Protocol\Header(
                channelId: $this->channelId,
                classId: Protocol\ClassType::BASIC,
                properties: MessageProperties::fromMessage($message),
            );

            foreach (Internal\chunks($message->body, $this->properties->maxFrame()) as $chunk) {
                yield new Protocol\Body(
                    channelId: $this->channelId,
                    body: $chunk,
                );
            }
        })());

        return $this->mode === ChannelMode::confirm ? $this->confirms->newConfirmation() : null;
    }

    /**
     * @throws \Throwable
     */
    public function get(string $queue = '', bool $noAck = false): ?Delivery
    {
        static $permit = true;
        if (!$permit) {
            throw Exception\OperationNotPermitted::forGet($this->channelId);
        }

        $permit = false;

        $this->connection->writeFrame(Protocol\Method::basicGet(
            channelId: $this->channelId,
            queue: $queue,
            noAck: $noAck,
        ));

        $frame = Future\awaitFirst([
            $this->hooks->oneshot($this->channelId, Frame\BasicGetOk::class),
            $this->hooks->oneshot($this->channelId, Frame\BasicGetEmpty::class),
        ]);

        /** @var ?Delivery $delivery */
        $delivery = null;

        if ($frame instanceof Frame\BasicGetOk) {
            $header = $this->await(Frame\ContentHeader::class);
            $n = $header->bodySize;
            $content = '';

            while ($n > 0) {
                $contentBody = $this->await(Frame\ContentBody::class);
                $content .= $contentBody->body;
                $n -= \strlen($contentBody->body);
            }

            $delivery = new Delivery(
                ack: $this->ack(...),
                nack: $this->nack(...),
                reject: $this->reject(...),
                body: $content,
                exchange: $frame->exchange,
                routingKey: $frame->routingKey,
                headers: $header->properties->headers,
                deliveryTag: $frame->deliveryTag,
                redelivered: $frame->redelivered,
                contentType: $header->properties->contentType,
                contentEncoding: $header->properties->contentEncoding,
                deliveryMode: $header->properties->deliveryMode,
                priority: $header->properties->priority,
                correlationId: $header->properties->correlationId,
                replyTo: $header->properties->replyTo,
                expiration: $header->properties->expiration,
                messageId: $header->properties->messageId,
                timestamp: $header->properties->timestamp,
                type: $header->properties->type,
                userId: $header->properties->userId,
                appId: $header->properties->appId,
            );
        }

        $permit = true;

        return $delivery;
    }

    /**
     * @throws \Throwable
     */
    public function ack(Delivery $delivery, bool $multiple = false): void
    {
        $this->connection->writeFrame(Protocol\Method::basicAck(
            channelId: $this->channelId,
            deliveryTag: $delivery->deliveryTag,
            multiple: $multiple,
        ));
    }

    /**
     * @throws \Throwable
     */
    public function nack(Delivery $delivery, bool $multiple = false, bool $requeue = true): void
    {
        $this->connection->writeFrame(Protocol\Method::basicNack(
            channelId: $this->channelId,
            deliveryTag: $delivery->deliveryTag,
            multiple: $multiple,
            requeue: $requeue,
        ));
    }

    /**
     * @throws \Throwable
     */
    public function reject(Delivery $delivery, bool $requeue = true): void
    {
        $this->connection->writeFrame(Protocol\Method::basicReject(
            channelId: $this->channelId,
            deliveryTag: $delivery->deliveryTag,
            requeue: $requeue,
        ));
    }

    /**
     * @throws \Throwable
     */
    public function recover(bool $requeue = false): void
    {
        $this->connection->writeFrame(Protocol\Method::basicRecover(
            channelId: $this->channelId,
            requeue: $requeue,
        ));

        $this->await(Frame\BasicRecoverOk::class);
    }

    /**
     * @param non-negative-int $prefetchSize
     * @param non-negative-int $prefetchCount
     * @throws \Throwable
     */
    public function qos(int $prefetchSize = 0, int $prefetchCount = 0, bool $global = false): void
    {
        $this->connection->writeFrame(Protocol\Method::basicQos(
            channelId: $this->channelId,
            prefetchSize: $prefetchSize,
            prefetchCount: $prefetchCount,
            global: $global,
        ));

        $this->await(Frame\BasicQosOk::class);
    }

    /**
     * @param callable(Delivery, self): void $callback
     * @param array<string, mixed> $arguments
     * @return non-empty-string
     * @throws \Throwable
     */
    public function consume(
        callable $callback,
        string $queue = '',
        string $consumerTag = '',
        bool $noLocal = false,
        bool $noAck = false,
        bool $exclusive = false,
        bool $noWait = false,
        array $arguments = [],
    ): string {
        if ($consumerTag === '') {
            $consumerTag = $this->consumerTags->next();
        }

        $this->connection->writeFrame(Protocol\Method::basicConsume(
            channelId: $this->channelId,
            queue: $queue,
            consumerTag: $consumerTag,
            noLocal: $noLocal,
            noAck: $noAck,
            exclusive: $exclusive,
            noWait: $noWait,
            arguments: $arguments,
        ));

        if (!$noWait) {
            $this->await(Frame\BasicConsumeOk::class);
        }

        $this->consumer->register($consumerTag, $callback);

        return $consumerTag;
    }

    /**
     * @param non-empty-string $consumerTag
     * @throws \Throwable
     */
    public function cancel(string $consumerTag, bool $noWait = false): void
    {
        $this->connection->writeFrame(Protocol\Method::basicCancel(
            channelId: $this->channelId,
            consumerTag: $consumerTag,
            noWait: $noWait,
        ));

        if (!$noWait) {
            $this->await(Frame\BasicCancelOk::class);
        }

        $this->consumer->unregister($consumerTag);
    }

    /**
     * @param non-empty-string $exchange
     * @param non-empty-string $exchangeType
     * @param array<string, mixed> $arguments
     * @throws \Throwable
     */
    public function exchangeDeclare(
        string $exchange,
        string $exchangeType = 'direct',
        bool $passive = false,
        bool $durable = false,
        bool $autoDelete = false,
        bool $internal = false,
        bool $noWait = false,
        array $arguments = [],
    ): void {
        $this->connection->writeFrame(Protocol\Method::exchangeDeclare(
            channelId: $this->channelId,
            exchange: $exchange,
            exchangeType: $exchangeType,
            passive: $passive,
            durable: $durable,
            autoDelete: $autoDelete,
            internal: $internal,
            noWait: $noWait,
            arguments: $arguments,
        ));

        if (!$noWait) {
            $this->await(Frame\ExchangeDeclareOk::class);
        }
    }

    /**
     * @param non-empty-string $destination
     * @param non-empty-string $source
     * @param array<string, mixed> $arguments
     * @throws \Throwable
     */
    public function exchangeBind(
        string $destination,
        string $source,
        string $routingKey = '',
        array $arguments = [],
        bool $noWait = false,
    ): void {
        $this->connection->writeFrame(Protocol\Method::exchangeBind(
            channelId: $this->channelId,
            destination: $destination,
            source: $source,
            routingKey: $routingKey,
            arguments: $arguments,
            noWait: $noWait,
        ));

        if (!$noWait) {
            $this->await(Frame\ExchangeBindOk::class);
        }
    }

    /**
     * @param non-empty-string $destination
     * @param non-empty-string $source
     * @param array<string, mixed> $arguments
     * @throws \Throwable
     */
    public function exchangeUnbind(
        string $destination,
        string $source,
        string $routingKey = '',
        array $arguments = [],
        bool $noWait = false,
    ): void {
        $this->connection->writeFrame(Protocol\Method::exchangeUnbind(
            channelId: $this->channelId,
            destination: $destination,
            source: $source,
            routingKey: $routingKey,
            arguments: $arguments,
            noWait: $noWait,
        ));

        if (!$noWait) {
            $this->await(Frame\ExchangeUnbindOk::class);
        }
    }

    /**
     * @param non-empty-string $exchange
     * @throws \Throwable
     */
    public function exchangeDelete(
        string $exchange,
        bool $ifUnused = false,
        bool $noWait = false,
    ): void {
        $this->connection->writeFrame(Protocol\Method::exchangeDelete(
            channelId: $this->channelId,
            exchange: $exchange,
            ifUnused: $ifUnused,
            noWait: $noWait,
        ));

        if (!$noWait) {
            $this->await(Frame\ExchangeDeleteOk::class);
        }
    }

    /**
     * @param array<string, mixed> $arguments
     * @throws \Throwable
     * @psalm-return ($noWait is true ? null : Queue)
     */
    public function queueDeclare(
        string $queue = '',
        bool $passive = false,
        bool $durable = false,
        bool $exclusive = false,
        bool $autoDelete = false,
        bool $noWait = false,
        array $arguments = [],
    ): ?Queue {
        $this->connection->writeFrame(Protocol\Method::queueDeclare(
            channelId: $this->channelId,
            queue: $queue,
            passive: $passive,
            durable: $durable,
            exclusive: $exclusive,
            autoDelete: $autoDelete,
            noWait: $noWait,
            arguments: $arguments,
        ));

        if ($noWait) {
            return null;
        }

        $frame = $this->await(Frame\QueueDeclareOk::class);

        return new Queue($frame->queue, $frame->messages, $frame->consumers);
    }

    /**
     * @param array<string, mixed> $arguments
     * @throws \Throwable
     */
    public function queueBind(
        string $queue = '',
        string $exchange = '',
        string $routingKey = '',
        bool $noWait = false,
        array $arguments = [],
    ): void {
        $this->connection->writeFrame(Protocol\Method::queueBind(
            channelId: $this->channelId,
            queue: $queue,
            exchange: $exchange,
            routingKey: $routingKey,
            arguments: $arguments,
            noWait: $noWait,
        ));

        if (!$noWait) {
            $this->await(Frame\QueueBindOk::class);
        }
    }

    /**
     * @param non-empty-string $queue
     * @param array<string, mixed> $arguments
     * @throws \Throwable
     */
    public function queueUnbind(
        string $queue,
        string $exchange = '',
        string $routingKey = '',
        array $arguments = [],
    ): void {
        $this->connection->writeFrame(Protocol\Method::queueUnbind(
            channelId: $this->channelId,
            queue: $queue,
            exchange: $exchange,
            routingKey: $routingKey,
            arguments: $arguments,
        ));

        $this->await(Frame\QueueUnbindOk::class);
    }

    /**
     * @param non-empty-string $queue
     * @return ($noWait is true ? null : non-negative-int)
     * @throws \Throwable
     */
    public function queuePurge(string $queue, bool $noWait = false): ?int
    {
        $this->connection->writeFrame(Protocol\Method::queuePurge(
            channelId: $this->channelId,
            queue: $queue,
            noWait: $noWait,
        ));

        if ($noWait) {
            return null;
        }

        return $this->await(Frame\QueuePurgeOk::class)->messages;
    }

    /**
     * @param non-empty-string $queue
     * @return ($noWait is true ? null : non-negative-int)
     * @throws \Throwable
     */
    public function queueDelete(
        string $queue,
        bool $ifUnused = false,
        bool $ifEmpty = false,
        bool $noWait = false,
    ): ?int {
        $this->connection->writeFrame(Protocol\Method::queueDelete(
            channelId: $this->channelId,
            queue: $queue,
            ifUnused: $ifUnused,
            ifEmpty: $ifEmpty,
            noWait: $noWait,
        ));

        if ($noWait) {
            return null;
        }

        return $this->await(Frame\QueueDeleteOk::class)->messages;
    }

    /**
     * @throws \Throwable
     */
    public function txSelect(): void
    {
        if ($this->mode->confirming()) {
            throw Exception\ChannelModeIsImpossible::inConfirmation($this->channelId);
        }

        if ($this->mode->transactional()) {
            throw Exception\ChannelModeIsImpossible::alreadyTransactional($this->channelId);
        }

        $this->connection->writeFrame(Protocol\Method::txSelect($this->channelId));

        $this->await(Frame\TxSelectOk::class);

        $this->mode = ChannelMode::transactional;
    }

    /**
     * @throws \Throwable
     */
    public function txCommit(): void
    {
        if (!$this->mode->transactional()) {
            throw Exception\ChannelIsNotTransactional::for($this->channelId);
        }

        $this->connection->writeFrame(Protocol\Method::txCommit($this->channelId));

        $this->await(Frame\TxCommitOk::class);
    }

    /**
     * @throws \Throwable
     */
    public function txRollback(): void
    {
        if (!$this->mode->transactional()) {
            throw Exception\ChannelIsNotTransactional::for($this->channelId);
        }

        $this->connection->writeFrame(Protocol\Method::txRollback($this->channelId));

        $this->await(Frame\TxRollbackOk::class);
    }

    /**
     * @throws \Throwable
     */
    public function confirmSelect(bool $noWait = false): void
    {
        if ($this->mode->transactional()) {
            throw Exception\ChannelModeIsImpossible::inTransactional($this->channelId);
        }

        if ($this->mode->confirming()) {
            throw Exception\ChannelModeIsImpossible::alreadyConfirming($this->channelId);
        }

        $this->connection->writeFrame(Protocol\Method::confirmSelect($this->channelId, $noWait));

        if (!$noWait) {
            $this->await(Frame\ConfirmSelectOk::class);
        }

        $this->mode = ChannelMode::confirm;
        $this->confirms->listen();
    }

    /**
     * @param non-negative-int $replyCode
     * @throws \Throwable
     */
    public function close(int $replyCode = 200, string $replyText = ''): void
    {
        if (!$this->isClosed) {
            $this->connection->writeFrame(Protocol\Method::channelClose($this->channelId, $replyCode, $replyText));

            $this->await(Frame\ChannelCloseOk::class);

            $this->isClosed = true;
        }
    }

    /**
     * @throws \Throwable
     */
    public function flow(bool $active): void
    {
        $this->connection->writeFrame(Protocol\Method::channelFlow($this->channelId, $active));

        $this->await(Frame\ChannelFlowOk::class);
    }

    public function abandon(\Throwable $e): void
    {
        $this->hooks->reject($this->channelId, $e);
        $this->hooks->unsubscribe($this->channelId);
    }

    /**
     * @template T of Frame
     * @param class-string<T> $frameType
     * @return T
     */
    private function await(
        string $frameType,
        Cancellation $cancellation = new NullCancellation(),
    ): Frame {
        return $this->hooks
            ->oneshot($this->channelId, $frameType)
            ->await($cancellation);
    }
}
