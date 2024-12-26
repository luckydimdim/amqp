<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol\Frame;

use Typhoon\Amqp091\Internal\Protocol\Frame;
use Typhoon\Amqp091\Internal\Protocol\Parser;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class ConnectionStart implements Frame
{
    public function __construct(
        public readonly int $versionMajor,
        public readonly int $versionMinor,
        public readonly array $serverProperties = [],
        public readonly string $mechanism = '',
        public readonly string $locales = '',
    ) {}

    public static function parse(Parser $parser): self
    {
        $versionMajor = $parser->readUint8();
        $versionMinor = $parser->readUint8();

        $serverProperties = $parser->readTable();
        $mechanism = $parser->readText();
        $locales = $parser->readText();

        return new self(
            $versionMajor,
            $versionMinor,
            $serverProperties,
            $mechanism,
            $locales,
        );
    }
}
