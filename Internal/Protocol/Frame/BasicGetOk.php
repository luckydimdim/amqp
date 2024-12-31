<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol\Frame;

use Typhoon\Amqp091\Internal\Io;
use Typhoon\Amqp091\Internal\Protocol\Frame;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class BasicGetOk implements Frame
{
    /**
     * @param non-negative-int $deliveryTag
     * @param non-negative-int $messageCount
     */
    public function __construct(
        public readonly int $deliveryTag,
        public readonly bool $redelivered,
        public readonly string $exchange,
        public readonly string $routingKey,
        public readonly int $messageCount,
    ) {}

    public static function read(Io\ReadBytes $reader): self
    {
        return new self(
            $reader->readUint64(),
            $reader->readBits(1)[0] ?? false,
            $reader->readString(),
            $reader->readString(),
            $reader->readUint32(),
        );
    }

    public function write(Io\WriteBytes $writer): void
    {
        $writer
            ->writeUint64($this->deliveryTag)
            ->writeBits($this->redelivered)
            ->writeString($this->exchange)
            ->writeString($this->routingKey)
            ->writeUint32($this->messageCount);
    }
}
