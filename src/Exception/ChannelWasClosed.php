<?php

declare(strict_types=1);

namespace Thesis\Amqp\Exception;

use Thesis\Amqp\AmqpException;

/**
 * @api
 */
final class ChannelWasClosed extends \RuntimeException implements AmqpException
{
    public function __construct(
        public readonly int $replyCode,
        public readonly string $replyText,
        ?\Throwable $previous = null,
    ) {
        parent::__construct("Channel was closed by the server: {$this->replyText}.", $this->replyCode, $previous);
    }
}
