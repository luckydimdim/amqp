<?php

declare(strict_types=1);

namespace Thesis\Amqp\Exception;

use Thesis\Amqp\AmqpException;

/**
 * @api
 */
final class UnknownValueType extends \UnexpectedValueException implements AmqpException
{
    public static function forValue(mixed $value): self
    {
        return new self(\sprintf('The value type "%s" is unknown.', get_debug_type($value)));
    }
}
