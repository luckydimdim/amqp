<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol\Frame;

use Typhoon\Amqp091\Internal\Io;
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
        public readonly string $mechanism,
        public readonly string $username,
        public readonly string $password,
        public readonly string $locale = 'en_US',
    ) {}

    public static function read(Io\ReadBytes $reader): self
    {
        return new self();
    }

    public function write(Io\WriteBytes $writer): Io\WriteBytes
    {
        return $writer
            ->writeTable($this->clientProperties)
            ->writeString($this->mechanism)
            ->reserve(endian::network->packUint32(...), function (Io\WriteBytes $writer): void {
                $writer
                    ->writeString('LOGIN')
                    ->writeValue($this->username)
                    ->writeString('PASSWORD')
                    ->writeValue($this->password);
            })
            ->writeString($this->locale);
    }
}
