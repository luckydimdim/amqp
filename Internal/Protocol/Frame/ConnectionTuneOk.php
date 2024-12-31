<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol\Frame;

use Typhoon\Amqp091\Internal\Io;
use Typhoon\Amqp091\Internal\Protocol\Frame;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class ConnectionTuneOk implements Frame
{
    /**
     * @param non-negative-int $channelMax
     * @param non-negative-int $frameMax
     * @param non-negative-int $heartbeat
     */
    public function __construct(
        public readonly int $channelMax,
        public readonly int $frameMax,
        public readonly int $heartbeat,
    ) {}

    public static function read(Io\ReadBytes $reader): self
    {
        return new self(
            $reader->readUint16(),
            $reader->readUint32(),
            $reader->readUint16(),
        );
    }

    public function write(Io\WriteBytes $writer): void
    {
        $writer
            ->writeUint16($this->channelMax)
            ->writeUint32($this->frameMax)
            ->writeUint16($this->heartbeat);
    }
}
