<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol\Frame;

use Typhoon\Amqp091\Internal\Io;
use Typhoon\Amqp091\Internal\Protocol\Frame;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class BasicAck implements Frame
{
    /**
     * @param non-negative-int $deliveryTag
     */
    public function __construct(
        public readonly int $deliveryTag,
        public readonly bool $multiple,
    ) {}

    public static function read(Io\ReadBytes $reader): self
    {
        return new self(
            $reader->readUint64(),
            $reader->readBits(1)[0] ?? false,
        );
    }

    public function write(Io\WriteBytes $writer): void
    {
        $writer
            ->writeUint64($this->deliveryTag)
            ->writeBits($this->multiple);
    }
}
