<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol\Frame;

use Typhoon\Amqp091\Internal\Io;
use Typhoon\Amqp091\Internal\Protocol\Frame;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class ChannelFlow implements Frame
{
    public function __construct(
        public readonly bool $active,
    ) {}

    public static function read(Io\ReadBytes $reader): self
    {
        return new self($reader->readBits(1)[0] ?? false);
    }

    public function write(Io\WriteBytes $writer): void
    {
        $writer->writeBits($this->active);
    }
}
