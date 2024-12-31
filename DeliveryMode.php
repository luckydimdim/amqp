<?php

declare(strict_types=1);

namespace Typhoon\Amqp091;

/**
 * @api
 */
enum DeliveryMode: int
{
    case Whatever = 0;
    case Transient = 1;
    case Persistent = 2;
}
