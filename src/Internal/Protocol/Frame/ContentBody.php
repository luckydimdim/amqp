<?php

declare(strict_types=1);

namespace Thesis\Amqp\Internal\Protocol\Frame;

use Thesis\Amqp\Exception\NotImplemented;
use Thesis\Amqp\Internal\Io;
use Thesis\Amqp\Internal\Protocol\Frame;

/**
 * @internal
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
