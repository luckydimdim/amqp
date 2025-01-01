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
     * @param non-empty-string $mechanism
     */
    final public static function create(string $mechanism, string $username, string $password): static
    {
        return match (strtoupper($mechanism)) {
            self::PLAIN => new Plain($username, $password),
            self::AMQPLAIN => new AMQPlain($username, $password),
            default => throw AuthenticationMechanismIsNotSupported::forClientMechanism($mechanism),
        };
    }

    /**
     * @param list<self> $selected
     * @param list<string> $available
     * @throws AuthenticationMechanismIsNotSupported
     */
    final public static function select(
        array $selected,
        array $available,
        string $username,
        string $password,
    ): self {
        if (\count($selected) === 0) {
            $selected = [new Plain($username, $password)];
        }

        foreach ($selected as $selectedMechanism) {
            if (\in_array($selectedMechanism->name(), $available, true)) {
                return $selectedMechanism;
            }
        }

        throw AuthenticationMechanismIsNotSupported::forServerMechanisms($available);
    }

    /**
     * @return self::*
     */
    abstract public function name(): string;

    abstract public function write(Io\WriteBytes $writer): void;
}
