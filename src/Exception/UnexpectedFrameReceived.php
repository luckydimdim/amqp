<?php

declare(strict_types=1);

namespace Thesis\Amqp\Exception;

use Thesis\Amqp\AmqpException;

/**
 * @api
 */
final class UnexpectedFrameReceived extends \UnexpectedValueException implements AmqpException
{
    /**
     * @param class-string $expects
     * @param class-string $actual
     */
    public static function forFrame(string $expects, string $actual): self
    {
        return new self("Expected frame {$expects}, but received {$actual}.");
    }
}
