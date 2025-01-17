<?php

declare(strict_types=1);

namespace Thesis\Amqp;

/**
 * @api
 * @phpstan-type AmqpScheme = Scheme|'amqp'|'amqps'
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
