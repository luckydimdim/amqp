<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol;

use Typhoon\Amqp091\Internal\Io;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
enum Heartbeat implements Frame
{
    case frame;

    public static function read(Io\ReadBytes $reader): self
    {
        return self::frame;
    }

    public function write(Io\WriteBytes $writer): Io\WriteBytes
    {
        return $writer
            ->writeUint8(FrameType::heartbeat->value)
            ->writeUint16(0)
            ->writeUint32(0)
            ->writeUint8(Protocol::FRAME_END);
    }
}
