<?php

declare(strict_types=1);

namespace Thesis\Amqp\Internal\Protocol;

use Thesis\Amqp\Internal\Io;

/**
 * @internal
 */
enum Heartbeat implements Frame
{
    case frame;

    public static function read(Io\ReadBytes $reader): self
    {
        return self::frame;
    }

    public function write(Io\WriteBytes $writer): void
    {
        $writer
            ->writeUint8(FrameType::heartbeat->value)
            ->writeUint16(0)
            ->writeUint32(0)
            ->writeUint8(Protocol::FRAME_END);
    }
}
