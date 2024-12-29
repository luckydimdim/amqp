<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol\Auth;

use Typhoon\Amqp091\Internal\Io;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
interface Authentication
{
    /**
     * @return non-empty-string
     */
    public function mechanism(): string;

    public function write(Io\WriteBytes $writer): void;
}
