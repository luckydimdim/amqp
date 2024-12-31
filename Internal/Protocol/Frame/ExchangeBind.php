<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol\Frame;

use Typhoon\Amqp091\Internal\Io;
use Typhoon\Amqp091\Internal\Protocol\Frame;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class ExchangeBind implements Frame
{
    /**
     * @param non-empty-string $destination
     * @param non-empty-string $source
     * @param array<string, mixed> $arguments
     * @param non-negative-int $reserved1
     */
    public function __construct(
        public readonly string $destination,
        public readonly string $source,
        public readonly string $routingKey = '',
        public readonly array $arguments = [],
        public readonly bool $noWait = false,
        public readonly int $reserved1 = 0,
    ) {}

    public static function read(Io\ReadBytes $reader): self
    {
        $reserved1 = $reader->readUint16();

        $destination = $reader->readString();
        \assert($destination !== '', 'destination must not be empty.');

        $source = $reader->readString();
        \assert($source !== '', 'source must not be empty.');

        $routingKey = $reader->readString();
        $noWait = $reader->readBits(1)[0] ?? false;
        $arguments = $reader->readTable();

        return new self(
            $destination,
            $source,
            $routingKey,
            $arguments,
            $noWait,
            $reserved1,
        );
    }

    public function write(Io\WriteBytes $writer): void
    {
        $writer
            ->writeUint16($this->reserved1)
            ->writeString($this->destination)
            ->writeString($this->source)
            ->writeString($this->routingKey)
            ->writeBits($this->noWait)
            ->writeTable($this->arguments);
    }
}
