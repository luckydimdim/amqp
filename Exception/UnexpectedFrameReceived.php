<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Exception;

use Typhoon\Amqp091\Amqp091Exception;

/**
 * @api
 */
final class UnexpectedFrameReceived extends \UnexpectedValueException implements Amqp091Exception
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
