<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
enum ChannelMode
{
    case regular;
    case transactional;
    case confirm;

    public function transactional(): bool
    {
        return $this === self::transactional;
    }

    public function confirming(): bool
    {
        return $this === self::confirm;
    }
}
