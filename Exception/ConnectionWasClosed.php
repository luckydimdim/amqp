<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Exception;

use Typhoon\Amqp091\Amqp091Exception;

/**
 * @api
 */
final class ConnectionWasClosed extends \RuntimeException implements Amqp091Exception
{
    public static function byServer(int $replyCode, string $replyText): self
    {
        return new self("Connection was closed by the server: {$replyText}.", $replyCode);
    }
}
