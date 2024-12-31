<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Exception;

use Typhoon\Amqp091\Amqp091Exception;

/**
 * @api
 */
final class OperationNotPermitted extends \LogicException implements Amqp091Exception
{
    /**
     * @param non-negative-int $channelId
     */
    public static function forGet(int $channelId): self
    {
        return new self("Another basic.get operation for channel {$channelId} is in progress.");
    }
}
