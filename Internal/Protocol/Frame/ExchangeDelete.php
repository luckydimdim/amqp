<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol\Frame;

use Typhoon\Amqp091\Internal\Io;
use Typhoon\Amqp091\Internal\Protocol\Frame;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class ExchangeDelete implements Frame
{
    /**
     * @param non-empty-string $exchange
     * @param non-negative-int $reserved1
     */
    public function __construct(
        public readonly string $exchange,
        public readonly bool $ifUnused = false,
        public readonly bool $noWait = false,
        public readonly int $reserved1 = 0,
    ) {}

    public static function read(Io\ReadBytes $reader): self
    {
        $reserved1 = $reader->readUint16();

        $exchange = $reader->readString();
        \assert($exchange !== '', 'exchange must not be empty.');

        [$ifUnused, $noWait] = $reader->readBits(2);

        return new self(
            $exchange,
            $ifUnused,
            $noWait,
            $reserved1,
        );
    }

    public function write(Io\WriteBytes $writer): Io\WriteBytes
    {
        return $writer
            ->writeUint16($this->reserved1)
            ->writeString($this->exchange)
            ->writeBits($this->ifUnused, $this->noWait);
    }
}
