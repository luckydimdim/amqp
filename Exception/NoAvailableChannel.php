<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Exception;

use Typhoon\Amqp091\Amqp091Exception;

/**
 * @api
 */
final class NoAvailableChannel extends \RuntimeException implements Amqp091Exception
{
    /**
     * @param non-negative-int $maxChannel
     */
    public static function forMaxChannel(int $maxChannel): self
    {
        return new self("You've used {$maxChannel} of the {$maxChannel} channels.");
    }
}
