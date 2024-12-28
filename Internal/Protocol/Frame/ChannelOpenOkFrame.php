<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol\Frame;

use Typhoon\Amqp091\Internal\Protocol\Frame;
use Typhoon\Amqp091\Internal\Io;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class ChannelOpenOkFrame implements Frame
{
    public function __construct(
        public readonly string $channelId,
    ) {}

    public static function read(Io\ReadBytes $reader): self
    {
        return new self($reader->readText());
    }

    public function write(Io\WriteBytes $writer): Io\WriteBytes
    {
        return $writer->writeText($this->channelId);
    }
}
