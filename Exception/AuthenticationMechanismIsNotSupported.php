<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Exception;

use Typhoon\Amqp091\Amqp091Exception;

/**
 * @api
 */
final class AuthenticationMechanismIsNotSupported extends \UnexpectedValueException implements Amqp091Exception
{
    /**
     * @param list<string> $mechanisms
     */
    public static function for(array $mechanisms): self
    {
        return new self(\sprintf('Authentication mechanisms "%s" is not supported.', implode(', ', $mechanisms)));
    }
}
