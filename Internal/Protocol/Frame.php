<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol;

use Typhoon\Amqp091\Internal\Io;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
interface Frame
{
    public static function read(Io\ReadBytes $reader): self;

    public function write(Io\WriteBytes $writer): Io\WriteBytes;
}
