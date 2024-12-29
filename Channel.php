<?php

declare(strict_types=1);

namespace Typhoon\Amqp091;

use Amp\Cancellation;
use Amp\NullCancellation;
use Typhoon\Amqp091\Internal\Io\AmqpConnection;
use Typhoon\Amqp091\Internal\Protocol;
use Typhoon\Amqp091\Internal\Protocol\Frame;

/**
 * @api
 */
final class Channel
{
    /**
     * @param non-negative-int $channelId
     */
    public function __construct(
        private readonly int $channelId,
        private readonly AmqpConnection $connection,
    ) {}

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
     * @template T of Protocol\Frame
     * @param class-string<T> $frameType
     * @return T
     */
    private function await(
        string $frameType,
        Cancellation $cancellation = new NullCancellation(),
    ): Frame {
        return $this->connection
            ->subscribe($this->channelId, $frameType)
            ->await($cancellation);
    }
}
