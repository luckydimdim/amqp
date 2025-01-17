<?php

declare(strict_types=1);

namespace Thesis\Amqp;

/**
 * @api
 */
enum DeliveryMode: int
{
    case Whatever = 0;
    case Transient = 1;
    case Persistent = 2;
}
