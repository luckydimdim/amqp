<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol\Frame;

use Typhoon\Amqp091\Internal\Io;
use Typhoon\Amqp091\Internal\Protocol\Frame;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
enum ProtocolHeader implements Frame
{
    case frame;

    public static function read(Io\ReadBytes $reader): self
    {
        // len('AMQP') + 4 * len(uint8).
        $reader->read(8);

        return self::frame;
    }

    public function write(Io\WriteBytes $writer): Io\WriteBytes
    {
        return $writer
            ->write('AMQP')
            ->writeUint8(0)
            ->writeUint8(0)
            ->writeUint8(9)
            ->writeUint8(1);
    }
}
