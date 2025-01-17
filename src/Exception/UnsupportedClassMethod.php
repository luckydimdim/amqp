<?php

declare(strict_types=1);

namespace Thesis\Amqp\Exception;

use Thesis\Amqp\AmqpException;

/**
 * @api
 */
final class UnsupportedClassMethod extends \UnexpectedValueException implements AmqpException
{
    public static function forClassMethod(int $classId, int $methodId): self
    {
        return new self("Unsupported class method {$classId}:{$methodId}.");
    }
}
