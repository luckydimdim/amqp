<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol\Frame;

use Typhoon\Amqp091\Internal\Io;
use Typhoon\Amqp091\Internal\Protocol\Frame;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class ConnectionTune implements Frame
{
    public function __construct(
        public readonly int $channelMax,
        public readonly int $frameMax,
        public readonly int $heartbeat,
    ) {}

    public static function read(Io\ReadBytes $reader): self
    {
        return new self(
            $reader->readInt16(),
            $reader->readInt32(),
            $reader->readInt16(),
        );
    }

    public function write(Io\WriteBytes $writer): Io\WriteBytes
    {
        return $writer
            ->writeInt16($this->channelMax)
            ->writeInt32($this->frameMax)
            ->writeInt16($this->heartbeat);
    }
}
