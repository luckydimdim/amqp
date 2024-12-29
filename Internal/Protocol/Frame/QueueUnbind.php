<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol\Frame;

use Typhoon\Amqp091\Internal\Io;
use Typhoon\Amqp091\Internal\Protocol\Frame;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class QueueUnbind implements Frame
{
    /**
     * @param non-empty-string $queue
     * @param array<string, mixed> $arguments
     * @param non-negative-int $reserved1
     */
    public function __construct(
        public readonly string $queue,
        public readonly string $exchange,
        public readonly string $routingKey,
        public readonly array $arguments = [],
        public readonly int $reserved1 = 0,
    ) {}

    public static function read(Io\ReadBytes $reader): self
    {
        $reserved1 = $reader->readUint16();
        $queue = $reader->readString();
        \assert($queue !== '', 'queue must not be empty.');

        $exchange = $reader->readString();
        $routingKey = $reader->readString();
        $arguments = $reader->readTable();

        return new self(
            $queue,
            $exchange,
            $routingKey,
            $arguments,
            $reserved1,
        );
    }

    public function write(Io\WriteBytes $writer): Io\WriteBytes
    {
        return $writer
            ->writeUint16($this->reserved1)
            ->writeString($this->queue)
            ->writeString($this->exchange)
            ->writeString($this->routingKey)
            ->writeTable($this->arguments);
    }
}
