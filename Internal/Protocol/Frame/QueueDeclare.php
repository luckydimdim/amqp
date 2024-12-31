<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol\Frame;

use Typhoon\Amqp091\Internal\Io;
use Typhoon\Amqp091\Internal\Protocol\Frame;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class QueueDeclare implements Frame
{
    /**
     * @param array<string, mixed> $arguments
     */
    public function __construct(
        public readonly string $queue,
        public readonly bool $passive = false,
        public readonly bool $durable = false,
        public readonly bool $exclusive = false,
        public readonly bool $autoDelete = false,
        public readonly bool $noWait = false,
        public readonly array $arguments = [],
        public readonly int $reserved1 = 0,
    ) {}

    public static function read(Io\ReadBytes $reader): self
    {
        $reserved1 = $reader->readUint16();
        $queue = $reader->readString();

        [$passive, $durable, $exclusive, $autoDelete, $noWait] = $reader->readBits(5);
        $arguments = $reader->readTable();

        return new self(
            $queue,
            $passive,
            $durable,
            $exclusive,
            $autoDelete,
            $noWait,
            $arguments,
            $reserved1,
        );
    }

    public function write(Io\WriteBytes $writer): void
    {
        $writer
            ->writeUint16(0)
            ->writeString($this->queue)
            ->writeBits($this->passive, $this->durable, $this->exclusive, $this->autoDelete, $this->noWait)
            ->writeTable($this->arguments);
    }
}
