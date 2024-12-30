<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol\Auth;

use Typhoon\Amqp091\Exception\AuthenticationMechanismIsNotSupported;
use Typhoon\Amqp091\Internal\Io;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
abstract class Mechanism
{
    final protected const PLAIN = 'PLAIN';
    final protected const AMQPLAIN = 'AMQPLAIN';

    /**
     * @param list<string> $mechanisms
     * @throws AuthenticationMechanismIsNotSupported
     */
    final public static function select(
        array $mechanisms,
        string $username,
        string $password,
    ): self {
        foreach ($mechanisms as $mechanismName) {
            $mechanism = match (strtoupper($mechanismName)) {
                self::PLAIN => new Plain($username, $password),
                self::AMQPLAIN => new AMQPlain($username, $password),
                default => null,
            };

            if ($mechanism !== null) {
                return $mechanism;
            }
        }

        throw AuthenticationMechanismIsNotSupported::for($mechanisms);
    }

    /**
     * @return self::*
     */
    abstract public function name(): string;

    abstract public function write(Io\WriteBytes $writer): void;
}
