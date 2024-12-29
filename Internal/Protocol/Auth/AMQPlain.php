<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol\Auth;

use Typhoon\Amqp091\Internal\Io;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class AMQPlain implements Authentication
{
    public function __construct(
        private readonly string $username,
        private readonly string $password,
    ) {}

    public function mechanism(): string
    {
        return 'AMQPLAIN';
    }

    public function write(Io\WriteBytes $writer): void
    {
        $writer
            ->writeString('LOGIN')
            ->writeValue($this->username)
            ->writeString('PASSWORD')
            ->writeValue($this->password);
    }
}
