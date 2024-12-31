<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol\Frame;

use Typhoon\Amqp091\Internal\Io;
use Typhoon\Amqp091\Internal\Protocol\Frame;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class BasicDeliver implements Frame
{
    /**
     * @param non-negative-int $deliveryTag
     */
    public function __construct(
        public readonly string $consumerTag,
        public readonly int $deliveryTag,
        public readonly bool $redelivered,
        public readonly string $exchange,
        public readonly string $routingKey,
    ) {}

    public static function read(Io\ReadBytes $reader): self
    {
        return new self(
            $reader->readString(),
            $reader->readUint64(),
            $reader->readBits(1)[0] ?? false,
            $reader->readString(),
            $reader->readString(),
        );
    }

    public function write(Io\WriteBytes $writer): void
    {
        $writer
            ->writeString($this->consumerTag)
            ->writeUint64($this->deliveryTag)
            ->writeBits($this->redelivered)
            ->writeString($this->exchange)
            ->writeString($this->routingKey);
    }
}
