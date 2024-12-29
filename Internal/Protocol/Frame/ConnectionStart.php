<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol\Frame;

use Typhoon\Amqp091\Internal\Io;
use Typhoon\Amqp091\Internal\Protocol\Frame;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 * @psalm-type ServerProperties = array{
 *     version: string,
 *     platform: string,
 *     cluster_name: string,
 *     capabilities?: array{
 *        publisher_confirms?: bool,
 *        direct_reply_to?: bool,
 *        per_consumer_qos?: bool,
 *     },
 * }
 */
final class ConnectionStart implements Frame
{
    /**
     * @param non-negative-int $versionMajor
     * @param non-negative-int $versionMinor
     * @param ServerProperties $serverProperties
     * @param list<string> $mechanisms
     */
    public function __construct(
        public readonly int $versionMajor,
        public readonly int $versionMinor,
        public readonly array $serverProperties,
        public readonly array $mechanisms = [],
        public readonly string $locales = '',
    ) {}

    public static function read(Io\ReadBytes $reader): self
    {
        [$versionMajor, $versionMinor] = [$reader->readUint8(), $reader->readUint8()];

        /** @var ServerProperties $serverProperties */
        $serverProperties = $reader->readTable();

        return new self(
            $versionMajor,
            $versionMinor,
            $serverProperties,
            explode(' ', $reader->readText()),
            $reader->readText(),
        );
    }

    public function write(Io\WriteBytes $writer): Io\WriteBytes
    {
        return $writer
            ->writeUint8($this->versionMajor)
            ->writeUint8($this->versionMinor)
            ->writeTable($this->serverProperties)
            ->writeText(implode(' ', $this->mechanisms))
            ->writeText($this->locales);
    }
}
