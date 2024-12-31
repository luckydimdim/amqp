<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol\Frame;

use Typhoon\Amqp091\Internal\Io;
use Typhoon\Amqp091\Internal\Protocol\Frame;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
enum TxSelect implements Frame
{
    case frame;

    public static function read(Io\ReadBytes $reader): self
    {
        return self::frame;
    }

    public function write(Io\WriteBytes $writer): void {}
}
