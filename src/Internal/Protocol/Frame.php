<?php

declare(strict_types=1);

namespace Thesis\Amqp\Internal\Protocol;

use Thesis\Amqp\Internal\Io;

/**
 * @internal
 */
interface Frame
{
    public static function read(Io\ReadBytes $reader): self;

    public function write(Io\WriteBytes $writer): void;
}
