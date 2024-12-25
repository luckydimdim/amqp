<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
enum FrameType: int
{
    case method = 1;
    case header = 2;
    case body = 3;
    case heartbeat = 8;
}
