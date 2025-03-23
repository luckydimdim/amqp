<?php

declare(strict_types=1);

namespace Thesis\Amqp;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;

#[CoversClass(Client::class)]
final class ClientTest extends TestCase
{
    #[DoesNotPerformAssertions]
    public function testDisconnectDoesNotThrowIfClientIsNotConnected(): void
    {
        $client = new Client(Config::default());
        $client->disconnect();
    }
}
