<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol\Frame;

use Typhoon\Amqp091\Internal\Io;
use Typhoon\Amqp091\Internal\Protocol\Frame;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class BasicPublish implements Frame
{
    /**
     * @param non-negative-int $reserved1
     */
    public function __construct(
        public readonly string $exchange,
        public readonly string $routingKey,
        public readonly bool $mandatory,
        public readonly bool $immediate,
        public readonly int $reserved1 = 0,
    ) {}

    public static function read(Io\ReadBytes $reader): self
    {
        $reserved1 = $reader->readUint16();
        $exchange = $reader->readString();
        $routingKey = $reader->readString();
        [$mandatory, $immediate] = $reader->readBits(2);

        return new self(
            $exchange,
            $routingKey,
            $mandatory,
            $immediate,
            $reserved1,
        );
    }

    public function write(Io\WriteBytes $writer): void
    {
        $writer
            ->writeUint16($this->reserved1)
            ->writeString($this->exchange)
            ->writeString($this->routingKey)
            ->writeBits($this->mandatory, $this->immediate);
    }
}
