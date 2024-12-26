<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Exception;

use Typhoon\Amqp091\Amqp091Exception;

/**
 * @api
 */
final class UnsupportedClassMethod extends \UnexpectedValueException implements Amqp091Exception
{
    public static function forClassMethod(int $classId, int $methodId): self
    {
        return new self("Unsupported class method {$classId}:{$methodId}.");
    }
}
