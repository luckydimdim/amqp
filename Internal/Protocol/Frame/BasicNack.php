<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol\Frame;

use Typhoon\Amqp091\Internal\Io;
use Typhoon\Amqp091\Internal\Protocol\Frame;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class BasicNack implements Frame
{
    /**
     * @param non-negative-int $deliveryTag
     */
    public function __construct(
        public readonly int $deliveryTag,
        public readonly bool $multiple,
        public readonly bool $requeue,
    ) {}

    public static function read(Io\ReadBytes $reader): self
    {
        $deliveryTag = $reader->readUint64();
        [$multiple, $requeue] = $reader->readBits(2);

        return new self(
            $deliveryTag,
            $multiple,
            $requeue,
        );
    }

    public function write(Io\WriteBytes $writer): void
    {
        $writer
            ->writeUint64($this->deliveryTag)
            ->writeBits($this->multiple, $this->requeue);
    }
}
