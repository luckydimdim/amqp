<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol\Frame;

use Typhoon\Amqp091\Internal\Io;
use Typhoon\Amqp091\Internal\Protocol\Auth\Authentication;
use Typhoon\Amqp091\Internal\Protocol\Frame;
use Typhoon\Endian\endian;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class ConnectionStartOk implements Frame
{
    /**
     * @param array<string, mixed> $clientProperties
     */
    public function __construct(
        public readonly array $clientProperties,
        public readonly Authentication $auth,
        public readonly string $locale = 'en_US',
    ) {}

    public static function read(Io\ReadBytes $reader): self
    {
        throw new \BadMethodCallException('Not implemented yet.');
    }

    public function write(Io\WriteBytes $writer): Io\WriteBytes
    {
        return $writer
            ->writeTable($this->clientProperties)
            ->writeString($this->auth->mechanism())
            ->reserve(endian::network->packUint32(...), $this->auth->write(...))
            ->writeString($this->locale);
    }
}
