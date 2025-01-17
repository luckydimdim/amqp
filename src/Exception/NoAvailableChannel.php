<?php

declare(strict_types=1);

namespace Thesis\Amqp\Exception;

use Thesis\Amqp\AmqpException;

/**
 * @api
 */
final class NoAvailableChannel extends \RuntimeException implements AmqpException
{
    /**
     * @param non-negative-int $maxChannel
     */
    public static function forMaxChannel(int $maxChannel): self
    {
        return new self("You have exceeded the channel limit ({$maxChannel}).");
    }
}
