<?php

declare(strict_types=1);

namespace Thesis\Amqp;

/**
 * @api
 */
final class Queue
{
    /**
     * @param non-empty-string $name
     * @param non-negative-int $messages
     * @param non-negative-int $consumers
     */
    public function __construct(
        public readonly string $name,
        public readonly int $messages,
        public readonly int $consumers,
    ) {}
}
