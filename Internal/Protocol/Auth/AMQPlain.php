<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol\Auth;

use Typhoon\Amqp091\Internal\Io;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class AMQPlain extends Mechanism
{
    public function __construct(
        private readonly string $username,
        private readonly string $password,
    ) {}

    public function name(): string
    {
        return self::AMQPLAIN;
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
