<?php

declare(strict_types=1);

namespace Thesis\Amqp\Internal;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Properties::class)]
final class PropertiesTest extends TestCase
{
    public function testCapabilities(): void
    {
        $properties = Properties::createDefault();
        self::assertTrue($properties->capable('basic.nack'));
        self::assertFalse($properties->capable('consumer_cancel_notify'));
    }

    public function testTune(): void
    {
        $properties = Properties::createDefault();
        self::assertSame(0xFFFF, $properties->maxChannel());
        self::assertSame(0xFFFF, $properties->maxFrame());
        $properties->tune(100, 200);
        self::assertSame(100, $properties->maxChannel());
        self::assertSame(200, $properties->maxFrame());
    }
}
