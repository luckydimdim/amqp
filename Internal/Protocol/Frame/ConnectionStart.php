<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol\Frame;

use Typhoon\Amqp091\Internal\Protocol\Frame;
use Typhoon\Amqp091\Internal\Protocol\Parser;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 * @psalm-type ServerProperties = array{
 *     version: string,
 *     platform: string,
 *     cluster_name: string,
 *     capabilities: array{
 *        publisher_confirms: bool,
 *        direct_reply_to: bool,
 *        per_consumer_qos: bool,
 *     },
 * }
 */
final class ConnectionStart implements Frame
{
    /**
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

    public static function parse(Parser $parser): self
    {
        [$versionMajor, $versionMinor] = [$parser->readUint8(), $parser->readUint8()];

        /** @var ServerProperties $serverProperties */
        $serverProperties = $parser->readTable();

        return new self(
            $versionMajor,
            $versionMinor,
            $serverProperties,
            explode(' ', $parser->readText()),
            $parser->readText(),
        );
    }
}
