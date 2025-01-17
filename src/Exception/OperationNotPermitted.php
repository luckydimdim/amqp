<?php

declare(strict_types=1);

namespace Thesis\Amqp\Exception;

use Thesis\Amqp\AmqpException;

/**
 * @api
 */
final class OperationNotPermitted extends \LogicException implements AmqpException
{
    /**
     * @param non-negative-int $channelId
     */
    public static function forGet(int $channelId): self
    {
        return new self("Another basic.get operation for channel {$channelId} is in progress.");
    }
}
