<?php

declare(strict_types=1);

namespace Typhoon\Amqp091;

/**
 * @api
 */
enum PublishResult
{
    case Acked;
    case Nacked;
    case Canceled;
    case Waiting;
}
