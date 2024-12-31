<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol\Frame;

use Typhoon\Amqp091\Internal\Io;
use Typhoon\Amqp091\Internal\Protocol\Frame;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class ChannelOpen implements Frame
{
    public function __construct(
        private readonly string $reserved1 = '',
    ) {}

    public static function read(Io\ReadBytes $reader): self
    {
        return new self($reader->readString());
    }

    public function write(Io\WriteBytes $writer): void
    {
        $writer->writeString($this->reserved1);
    }
}
