<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol;

use Typhoon\ByteOrder\ReadFrom;
use Typhoon\Endian\endian;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
interface Frame
{
    public static function parse(ReadFrom $reader, endian $endian = endian::network): self;
}
