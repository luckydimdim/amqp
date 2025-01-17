<?php

declare(strict_types=1);

namespace Thesis\Amqp\Internal\Protocol\Frame;

use Thesis\Amqp\Internal\Io;
use Thesis\Amqp\Internal\Protocol\Frame;

/**
 * @internal
 */
final class BasicQos implements Frame
{
    /**
     * @param non-negative-int $prefetchSize
     * @param non-negative-int $prefetchCount
     */
    public function __construct(
        public readonly int $prefetchSize,
        public readonly int $prefetchCount,
        public readonly bool $global,
    ) {}

    public static function read(Io\ReadBytes $reader): self
    {
        return new self(
            $reader->readUint32(),
            $reader->readUint16(),
            $reader->readBits(1)[0],
        );
    }

    public function write(Io\WriteBytes $writer): void
    {
        $writer
            ->writeUint32($this->prefetchSize)
            ->writeUint16($this->prefetchCount)
            ->writeBits($this->global);
    }
}
