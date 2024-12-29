<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol\Frame;

use Typhoon\Amqp091\Internal\Io;
use Typhoon\Amqp091\Internal\Protocol\Frame;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class QueueDeclareOk implements Frame
{
    /**
     * @param non-empty-string $queue
     * @param non-negative-int $messages
     * @param non-negative-int $consumers
     */
    public function __construct(
        public readonly string $queue,
        public readonly int $messages,
        public readonly int $consumers,
    ) {}

    public static function read(Io\ReadBytes $reader): self
    {
        /** @var non-empty-string $queue */
        $queue = $reader->readString();

        /** @var non-negative-int $messages */
        $messages = $reader->readInt32();

        /** @var non-negative-int $consumers */
        $consumers = $reader->readInt32();

        return new self(
            $queue,
            $messages,
            $consumers,
        );
    }

    public function write(Io\WriteBytes $writer): Io\WriteBytes
    {
        return $writer
            ->writeString($this->queue)
            ->writeInt32($this->messages)
            ->writeInt32($this->consumers);
    }
}
