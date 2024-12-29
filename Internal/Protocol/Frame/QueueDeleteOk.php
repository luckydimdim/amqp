<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol\Frame;

use Typhoon\Amqp091\Internal\Io;
use Typhoon\Amqp091\Internal\Protocol\Frame;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class QueueDeleteOk implements Frame
{
    /**
     * @param non-negative-int $messages
     */
    public function __construct(
        public readonly int $messages,
    ) {}

    public static function read(Io\ReadBytes $reader): self
    {
        return new self($reader->readUint32());
    }

    public function write(Io\WriteBytes $writer): Io\WriteBytes
    {
        return $writer->writeUint32($this->messages);
    }
}
