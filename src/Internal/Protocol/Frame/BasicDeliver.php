<?php

declare(strict_types=1);

namespace Thesis\Amqp\Internal\Protocol\Frame;

use Thesis\Amqp\Internal\Io;
use Thesis\Amqp\Internal\Protocol\Frame;

/**
 * @internal
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
            $reader->readBits(1)[0],
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
