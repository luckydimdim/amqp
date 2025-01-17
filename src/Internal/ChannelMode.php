<?php

declare(strict_types=1);

namespace Thesis\Amqp\Internal;

/**
 * @internal
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
