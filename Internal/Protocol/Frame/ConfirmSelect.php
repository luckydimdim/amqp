<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol\Frame;

use Typhoon\Amqp091\Internal\Io;
use Typhoon\Amqp091\Internal\Protocol\Frame;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class ConfirmSelect implements Frame
{
    public function __construct(
        public readonly bool $noWait = false,
    ) {}

    public static function read(Io\ReadBytes $reader): Frame
    {
        return new self($reader->readBits(1)[0] ?? false);
    }

    public function write(Io\WriteBytes $writer): Io\WriteBytes
    {
        return $writer->writeBits($this->noWait);
    }
}
