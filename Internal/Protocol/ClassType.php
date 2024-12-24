<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
enum ClassType: int
{
    case connection = 10;
    case channel = 20;
    case access = 30;
    case exchange = 40;
    case queue = 50;
    case basic = 60;
    case tx = 90;
    case confirm = 85;
}
