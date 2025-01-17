<?php

declare(strict_types=1);

namespace Thesis\Amqp\Internal\Protocol\Frame;

use Thesis\Amqp\Internal\Io;
use Thesis\Amqp\Internal\Protocol\Frame;

/**
 * @internal
 */
enum ConfirmSelectOk implements Frame
{
    case frame;

    public static function read(Io\ReadBytes $reader): Frame
    {
        return self::frame;
    }

    public function write(Io\WriteBytes $writer): void {}
}
