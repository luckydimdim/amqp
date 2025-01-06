<?php

declare(strict_types=1);

namespace Typhoon\Amqp091;

/**
 * @api
 * @psalm-type AmqpScheme = Scheme|'amqp'|'amqps'
 */
enum Scheme: string
{
    case amqp = 'amqp';
    case amqps = 'amqps';

    /**
     * @param AmqpScheme $scheme
     */
    public static function parse(self|string $scheme): self
    {
        if (\is_string($scheme)) {
            $scheme = self::from($scheme);
        }

        return $scheme;
    }
}
