<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol\Frame;

use Typhoon\Amqp091\Internal\Io;
use Typhoon\Amqp091\Internal\Protocol\Frame;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class ConnectionOpen implements Frame
{
    /**
     * @param non-empty-string $vhost
     */
    public function __construct(
        public readonly string $vhost,
        public readonly string $reserved1 = '',
        public readonly bool $reserved2 = false,
    ) {}

    public static function read(Io\ReadBytes $reader): self
    {
        return new self(
            $reader->readString(),
            $reader->readString(),
            $reader->readBits(1)[0] ?? false,
        );
    }

    public function write(Io\WriteBytes $writer): Io\WriteBytes
    {
        return $writer
            ->writeString($this->vhost)
            ->writeString($this->reserved1)
            ->writeBits($this->reserved2);
    }
}
