<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol\Frame;

use Typhoon\Amqp091\Internal\Io;
use Typhoon\Amqp091\Internal\Protocol\Frame;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class QueueBind implements Frame
{
    /**
     * @param array<string, mixed> $arguments
     */
    public function __construct(
        public readonly string $queue,
        public readonly string $exchange,
        public readonly string $routingKey,
        public readonly array $arguments = [],
        public readonly bool $noWait = false,
        public readonly int $reserved1 = 0,
    ) {}

    public static function read(Io\ReadBytes $reader): self
    {
        $reserved1 = $reader->readUint16();
        $queue = $reader->readString();
        $exchange = $reader->readString();
        $routingKey = $reader->readString();
        $noWait = $reader->readBits(1)[0] ?? false;
        $arguments = $reader->readTable();

        return new self(
            $queue,
            $exchange,
            $routingKey,
            $arguments,
            $noWait,
            $reserved1,
        );
    }

    public function write(Io\WriteBytes $writer): void
    {
        $writer
            ->writeInt16(0)
            ->writeString($this->queue)
            ->writeString($this->exchange)
            ->writeString($this->routingKey)
            ->writeBits($this->noWait)
            ->writeTable($this->arguments);
    }
}
