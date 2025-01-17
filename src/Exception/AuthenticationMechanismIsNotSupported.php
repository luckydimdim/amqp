<?php

declare(strict_types=1);

namespace Thesis\Amqp\Exception;

use Thesis\Amqp\AmqpException;

/**
 * @api
 */
final class AuthenticationMechanismIsNotSupported extends \UnexpectedValueException implements AmqpException
{
    /**
     * @param list<string> $mechanisms
     */
    public static function forServerMechanisms(array $mechanisms): self
    {
        return new self(\sprintf('Authentication server mechanisms "%s" is not supported.', implode(', ', $mechanisms)));
    }

    public static function forClientMechanism(string $mechanism): self
    {
        return new self(\sprintf('Authentication client mechanism "%s" is not supported.', $mechanism));
    }
}
