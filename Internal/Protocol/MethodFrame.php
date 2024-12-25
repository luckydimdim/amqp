<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol;

/**
 * @api
 */
final class MethodFrame
{
    public function __construct(
        public readonly int $channelId,
        public readonly Frame $frame,
    ) {}
}
