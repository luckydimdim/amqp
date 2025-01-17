<?php

declare(strict_types=1);

namespace Thesis\Amqp\Exception;

use Thesis\Amqp\AmqpException;

/**
 * @api
 */
final class ChannelModeIsImpossible extends \LogicException implements AmqpException
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

    /**
     * @param non-negative-int $channelId
     */
    public static function alreadyTransactional(int $channelId): self
    {
        return new self("Channel {$channelId} is already transactional.");
    }

    /**
     * @param non-negative-int $channelId
     */
    public static function alreadyConfirming(int $channelId): self
    {
        return new self("Channel {$channelId} is already confirming.");
    }
}
