<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol;

use Typhoon\Amqp091\Exception\NotImplemented;
use Typhoon\Amqp091\Internal\Io;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class Body implements Frame
{
    /**
     * @param non-negative-int $channelId
     */
    public function __construct(
        public readonly int $channelId,
        public readonly string $body,
    ) {}

    public static function read(Io\ReadBytes $reader): self
    {
        throw new NotImplemented();
    }

    public function write(Io\WriteBytes $writer): void
    {
        $writer
            ->writeUint8(FrameType::body->value)
            ->writeUint16($this->channelId)
            ->writeUint32(\strlen($this->body))
            ->write($this->body)
            ->writeUint8(Protocol::FRAME_END);
    }
}
