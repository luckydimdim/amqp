<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Exception;

use Typhoon\Amqp091\Amqp091Exception;

/**
 * @api
 */
final class UnknownValueType extends \UnexpectedValueException implements Amqp091Exception
{
    public static function forValue(mixed $value): self
    {
        return new self(\sprintf('The value type "%s" is unknown.', get_debug_type($value)));
    }
}
