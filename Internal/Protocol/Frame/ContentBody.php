<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol\Frame;

use Typhoon\Amqp091\Exception\NotImplemented;
use Typhoon\Amqp091\Internal\Io;
use Typhoon\Amqp091\Internal\Protocol\Frame;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class ContentBody implements Frame
{
    public function __construct(
        public readonly string $body,
    ) {}

    public static function read(Io\ReadBytes $reader): self
    {
        return new self($reader->reset());
    }

    public function write(Io\WriteBytes $writer): void
    {
        throw new NotImplemented();
    }
}
