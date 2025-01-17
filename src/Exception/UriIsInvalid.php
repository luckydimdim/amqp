<?php

declare(strict_types=1);

namespace Thesis\Amqp\Exception;

use Thesis\Amqp\AmqpException;

/**
 * @api
 */
final class UriIsInvalid extends \UnexpectedValueException implements AmqpException
{
    public static function invalidScheme(string $scheme): self
    {
        return new self(\sprintf('The scheme "%s" is incorrect, expected "amqp" or "amqps".', $scheme));
    }
}
