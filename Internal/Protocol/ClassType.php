<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
interface ClassType
{
    public const CONNECTION = 10;
    public const CHANNEL = 20;
    public const ACCESS = 30;
    public const EXCHANGE = 40;
    public const QUEUE = 50;
    public const BASIC = 60;
    public const TX = 90;
    public const CONFIRM = 85;
}
