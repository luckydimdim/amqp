<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Exception;

use Typhoon\Amqp091\Amqp091Exception;

/**
 * @api
 */
final class ChannelWasClosed extends \RuntimeException implements Amqp091Exception
{
    public function __construct(
        public readonly int $replyCode,
        public readonly string $replyText,
        ?\Throwable $previous = null,
    ) {
        parent::__construct("Channel was closed by the server {$this->replyText}.", $this->replyCode, $previous);
    }
}
