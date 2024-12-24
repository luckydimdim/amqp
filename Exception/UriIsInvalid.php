<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Exception;

use Typhoon\Amqp091\Amqp091Exception;

/**
 * @api
 */
final class UriIsInvalid extends \UnexpectedValueException implements Amqp091Exception
{
    public static function invalidScheme(string $scheme): self
    {
        return new self(\sprintf('The scheme "%s" is incorrect, expected "amqp" or "amqps".', $scheme));
    }
}
