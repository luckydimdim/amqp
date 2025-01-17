<?php

declare(strict_types=1);

namespace Thesis\Amqp\Internal;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Thesis\Amqp\Exception\AuthenticationMechanismIsNotSupported;
use Thesis\Amqp\Internal\Protocol\Auth\AMQPlain;
use Thesis\Amqp\Internal\Protocol\Auth\Mechanism;
use Thesis\Amqp\Internal\Protocol\Auth\Plain;

#[CoversClass(Mechanism::class)]
final class MechanismTest extends TestCase
{
    public function testMechanismNotAvailable(): void
    {
        self::expectException(AuthenticationMechanismIsNotSupported::class);
        self::expectExceptionMessage('Authentication client mechanism "invalid" is not supported.');
        Mechanism::create('invalid', 'guest', 'guest');
    }

    public function testMechanismOrdered(): void
    {
        $mechanism = Mechanism::select(
            [Mechanism::create('amqplain', 'guest', 'guest'), Mechanism::create('plain', 'guest', 'guest')],
            ['PLAIN', 'AMQPLAIN'],
        );
        self::assertInstanceOf(AMQPlain::class, $mechanism);
    }

    public function testServerMechanismNotAvailable(): void
    {
        self::expectException(AuthenticationMechanismIsNotSupported::class);
        self::expectExceptionMessage('Authentication server mechanisms "EXTERNAL" is not supported.');
        Mechanism::select(
            [new Plain('guest', 'guest')],
            ['EXTERNAL'],
        );
    }
}
