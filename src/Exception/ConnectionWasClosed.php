<?php

declare(strict_types=1);

namespace Thesis\Amqp\Exception;

use Thesis\Amqp\AmqpException;

/**
 * @api
 */
final class ConnectionWasClosed extends \RuntimeException implements AmqpException
{
    public static function byServer(int $replyCode, string $replyText): self
    {
        return new self("Connection was closed by the server: {$replyText}.", $replyCode);
    }
}
