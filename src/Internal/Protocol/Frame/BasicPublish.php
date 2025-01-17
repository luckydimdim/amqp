<?php

declare(strict_types=1);

namespace Thesis\Amqp\Internal\Protocol\Frame;

use Thesis\Amqp\Internal\Io;
use Thesis\Amqp\Internal\Protocol\Frame;

/**
 * @internal
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
