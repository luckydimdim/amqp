<?php

declare(strict_types=1);

namespace Thesis\Amqp\Exception;

use Thesis\Amqp\AmqpException;

/**
 * @api
 */
final class ChannelIsNotTransactional extends \LogicException implements AmqpException
{
    /**
     * @param non-negative-int $channelId
     */
    public static function for(int $channelId): self
    {
        return new self("Channel {$channelId} is not transactional.");
    }
}
