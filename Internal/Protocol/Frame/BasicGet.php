<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol\Frame;

use Typhoon\Amqp091\Internal\Io;
use Typhoon\Amqp091\Internal\Protocol\Frame;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class BasicGet implements Frame
{
    /**
     * @param non-negative-int $reserved1
     */
    public function __construct(
        public readonly string $queue,
        public readonly bool $noAck = false,
        public readonly int $reserved1 = 0,
    ) {}

    public static function read(Io\ReadBytes $reader): self
    {
        $reserved1 = $reader->readUint16();
        $queue = $reader->readString();
        $noAck = $reader->readBits(1)[0] ?? false;

        return new self(
            $queue,
            $noAck,
            $reserved1,
        );
    }

    public function write(Io\WriteBytes $writer): void
    {
        $writer
            ->writeUint16($this->reserved1)
            ->writeString($this->queue)
            ->writeBits($this->noAck);
    }
}
