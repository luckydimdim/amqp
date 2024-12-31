<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol\Frame;

use Typhoon\Amqp091\Internal\Io;
use Typhoon\Amqp091\Internal\Protocol\Frame;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class BasicCancel implements Frame
{
    public function __construct(
        public readonly string $consumerTag,
        public readonly bool $noWait,
    ) {}

    public static function read(Io\ReadBytes $reader): self
    {
        return new self(
            $reader->readString(),
            $reader->readBits(1)[0] ?? false,
        );
    }

    public function write(Io\WriteBytes $writer): void
    {
        $writer
            ->writeString($this->consumerTag)
            ->writeBits($this->noWait);
    }
}
