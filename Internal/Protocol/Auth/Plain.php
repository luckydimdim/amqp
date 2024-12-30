<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol\Auth;

use Typhoon\Amqp091\Internal\Io;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class Plain extends Mechanism
{
    public function __construct(
        public readonly string $username,
        public readonly string $password,
    ) {}

    public function name(): string
    {
        return self::PLAIN;
    }

    public function write(Io\WriteBytes $writer): void
    {
        $writer->write("\000{$this->username}\000{$this->password}");
    }
}
