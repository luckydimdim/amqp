<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol;

use Typhoon\ByteOrder\ReadFrom;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
enum Frame: int
{
    case method = 1;
    case header = 2;
    case body = 3;
    case heartbeat = 8;

    public function parse(ReadFrom $reader)
    {
        //
    }
}
