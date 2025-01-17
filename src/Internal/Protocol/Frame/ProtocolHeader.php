<?php

declare(strict_types=1);

namespace Thesis\Amqp\Internal\Protocol\Frame;

use Thesis\Amqp\Internal\Io;
use Thesis\Amqp\Internal\Protocol\Frame;

/**
 * @internal
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

    public function write(Io\WriteBytes $writer): void
    {
        $writer
            ->write('AMQP')
            ->writeUint8(0)
            ->writeUint8(0)
            ->writeUint8(9)
            ->writeUint8(1);
    }
}
