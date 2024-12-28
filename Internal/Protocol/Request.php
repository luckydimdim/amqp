<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class Request
{
    /**
     * @param non-negative-int $channelId
     */
    public function __construct(
        public readonly int $channelId,
        public readonly Frame $frame,
    ) {}
}
