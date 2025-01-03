<?php

declare(strict_types=1);

namespace Typhoon\Amqp091;

/**
 * @api
 */
enum PublishedResult
{
    case Acked;
    case Nacked;
    case Canceled;
    case Waiting;
}
