<?php

declare(strict_types=1);

namespace Typhoon\Amqp091;

use Amp\Cancellation;
use Amp\DeferredFuture;
use Amp\NullCancellation;
use Typhoon\Amqp091\Internal\ChannelMode;
use Typhoon\Amqp091\Internal\Io\AmqpConnection;
use Typhoon\Amqp091\Internal\Monitor;
use Typhoon\Amqp091\Internal\Protocol;
use Typhoon\Amqp091\Internal\Protocol\Frame;

/**
 * @api
 */
final class Channel
{
    private readonly Monitor $monitor;

    private ChannelMode $mode = ChannelMode::regular;

    /**
     * @param non-negative-int $channelId
     */
    public function __construct(
        private readonly int $channelId,
        private readonly AmqpConnection $connection,
    ) {
        $this->monitor = new Monitor();
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

        $this->connection->writeFrame(Protocol\Method::confirmSelect($this->channelId, $noWait));

        if (!$noWait) {
            $this->await(Frame\ConfirmSelectOk::class);
        }

        $this->mode = ChannelMode::confirm;
    }

    /**
     * @param non-negative-int $replyCode
     * @throws \Throwable
     */
    public function close(int $replyCode = 200, string $replyText = ''): void
    {
        $this->connection->writeFrame(Protocol\Method::channelClose($this->channelId, $replyCode, $replyText));

        $this->await(Frame\ChannelCloseOk::class);
    }

    public function abandon(\Throwable $e): void
    {
        $this->connection->unsubscribe($this->channelId);
        $this->monitor->cancel($e);
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
        /** @var DeferredFuture<T> $deferred */
        $deferred = new DeferredFuture();
        $this->monitor->trace($deferred);

        $this->connection
            ->subscribe($this->channelId, $frameType)
            ->map($deferred->complete(...));

        return $deferred
            ->getFuture()
            ->await($cancellation);
    }
}
