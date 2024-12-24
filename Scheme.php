<?php

declare(strict_types=1);

namespace Typhoon\Amqp091;

/**
 * @api
 */
enum Scheme: string
{
    case amqp = 'amqp';
    case amqps = 'amqps';
}
