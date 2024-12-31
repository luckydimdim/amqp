<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol\Frame;

use Typhoon\Amqp091\Internal\Io;
use Typhoon\Amqp091\Internal\Protocol\Frame;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class QueueDelete implements Frame
{
    /**
     * @param non-empty-string $queue
     * @param non-negative-int $reserved1
     */
    public function __construct(
        public readonly string $queue,
        public readonly bool $ifUnused = false,
        public readonly bool $ifEmpty = false,
        public readonly bool $noWait = false,
        public readonly int $reserved1 = 0,
    ) {}

    public static function read(Io\ReadBytes $reader): self
    {
        $reserved1 = $reader->readUint16();
        $queue = $reader->readString();
        \assert($queue !== '', 'queue must not be empty.');

        [$ifUnused, $ifEmpty, $noWait] = $reader->readBits(3);

        return new self(
            $queue,
            $ifUnused,
            $ifEmpty,
            $noWait,
            $reserved1,
        );
    }

    public function write(Io\WriteBytes $writer): void
    {
        $writer
            ->writeUint16($this->reserved1)
            ->writeString($this->queue)
            ->writeBits($this->ifUnused, $this->ifEmpty, $this->noWait);
    }
}
