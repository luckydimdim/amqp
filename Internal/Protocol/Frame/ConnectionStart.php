<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol\Frame;

use Typhoon\Amqp091\Internal\Protocol\Frame;
use Typhoon\ByteOrder\ReadFrom;
use Typhoon\Endian\endian;
use function Typhoon\Amqp091\Internal\readTable;
use function Typhoon\Amqp091\Internal\readText;

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

    public static function parse(ReadFrom $reader, endian $endian = endian::network): self
    {
        $versionMajor = $reader->readUint8($endian);
        $versionMinor = $reader->readUint8($endian);

        [$serverProperties] = readTable($reader, $endian);
        [$mechanism] = readText($reader, $endian);
        [$locales] = readText($reader, $endian);

        return new self(
            $versionMajor,
            $versionMinor,
            $serverProperties,
            $mechanism,
            $locales,
        );
    }
}
