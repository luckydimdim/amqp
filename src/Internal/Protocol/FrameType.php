<?php

declare(strict_types=1);

namespace Thesis\Amqp\Internal\Protocol;

/**
 * @internal
 */
enum FrameType: int
{
    case method = 1;
    case header = 2;
    case body = 3;
    case heartbeat = 8;
}
