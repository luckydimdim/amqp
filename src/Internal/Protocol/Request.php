<?php

declare(strict_types=1);

namespace Thesis\Amqp\Internal\Protocol;

/**
 * @internal
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
