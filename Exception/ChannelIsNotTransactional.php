<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Exception;

use Typhoon\Amqp091\Amqp091Exception;

/**
 * @api
 */
final class ChannelIsNotTransactional extends \LogicException implements Amqp091Exception
{
    /**
     * @param non-negative-int $channelId
     */
    public static function for(int $channelId): self
    {
        return new self("Channel {$channelId} is not transactional.");
    }
}
