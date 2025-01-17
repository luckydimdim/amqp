<?php

declare(strict_types=1);

namespace Thesis\Amqp\Internal\Protocol\Frame;

use Thesis\Amqp\Internal\Io;
use Thesis\Amqp\Internal\Protocol\Frame;

/**
 * @internal
 */
final class BasicConsume implements Frame
{
    /**
     * @param array<string, mixed> $arguments
     * @param non-negative-int $reserved1
     */
    public function __construct(
        public readonly string $queue,
        public readonly string $consumerTag,
        public readonly bool $noLocal,
        public readonly bool $noAck,
        public readonly bool $exclusive,
        public readonly bool $noWait,
        public readonly array $arguments,
        public readonly int $reserved1 = 0,
    ) {}

    public static function read(Io\ReadBytes $reader): self
    {
        $reserved1 = $reader->readUint16();
        $queue = $reader->readString();
        $consumerTag = $reader->readString();
        [$noLocal, $noAck, $exclusive, $noWait] = $reader->readBits(4);
        $arguments = $reader->readTable();

        return new self(
            $queue,
            $consumerTag,
            $noLocal,
            $noAck,
            $exclusive,
            $noWait,
            $arguments,
            $reserved1,
        );
    }

    public function write(Io\WriteBytes $writer): void
    {
        $writer
            ->writeUint16($this->reserved1)
            ->writeString($this->queue)
            ->writeString($this->consumerTag)
            ->writeBits($this->noLocal, $this->noAck, $this->exclusive, $this->noWait)
            ->writeTable($this->arguments);
    }
}
