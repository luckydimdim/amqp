<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol;

use Typhoon\Amqp091\Exception\NotImplemented;
use Typhoon\Amqp091\Internal\Io;
use Typhoon\Amqp091\Internal\MessageProperties;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class Header implements Frame
{
    /**
     * @param non-negative-int $channelId
     * @param ClassType::* $classId
     * @param non-negative-int $weight
     */
    public function __construct(
        public readonly int $channelId,
        public readonly int $classId,
        public readonly MessageProperties $properties,
        public readonly int $weight = 0,
    ) {}

    public static function read(Io\ReadBytes $reader): self
    {
        throw new NotImplemented();
    }

    public function write(Io\WriteBytes $writer): void
    {
        $writer = $writer
            ->writeUint8(FrameType::header->value)
            ->writeUint16($this->channelId)
            ->writeUint32(14 + $this->properties->size())
            ->writeUint16($this->classId)
            ->writeUint16($this->weight)
            ->writeUint64($this->properties->bodyLen)
            ->writeUint16($mask = $this->properties->mask());

        $this->properties
            ->write($writer, $mask)
            ->writeUint8(Protocol::FRAME_END);
    }
}
