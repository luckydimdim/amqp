<?php

declare(strict_types=1);

namespace Thesis\Amqp\Exception;

use Thesis\Amqp\AmqpException;

/**
 * @api
 */
final class ConnectionIsClosed extends \RuntimeException implements AmqpException {}
