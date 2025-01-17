<?php

declare(strict_types=1);

namespace Thesis\Amqp\Internal\Protocol\Frame;

use Thesis\Amqp\Internal\Io;
use Thesis\Amqp\Internal\Protocol\Frame;

/**
 * @internal
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
