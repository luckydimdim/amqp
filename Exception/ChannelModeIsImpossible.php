<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Exception;

use Typhoon\Amqp091\Amqp091Exception;

/**
 * @api
 */
final class ChannelModeIsImpossible extends \LogicException implements Amqp091Exception
{
    /**
     * @param non-negative-int $channelId
     */
    public static function inConfirmation(int $channelId): self
    {
        return new self("Cannot put confirming channel {$channelId} in transactional mode.");
    }

    /**
     * @param non-negative-int $channelId
     */
    public static function inTransactional(int $channelId): self
    {
        return new self("Cannot put transactional channel {$channelId} in confirming mode.");
    }
}
