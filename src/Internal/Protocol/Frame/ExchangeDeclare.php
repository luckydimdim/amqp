<?php

declare(strict_types=1);

namespace Thesis\Amqp\Internal\Protocol\Frame;

use Thesis\Amqp\Internal\Io;
use Thesis\Amqp\Internal\Protocol\Frame;

/**
 * @internal
 */
final class ExchangeDeclare implements Frame
{
    /**
     * @param non-empty-string $exchange
     * @param non-empty-string $exchangeType
     * @param array<string, mixed> $arguments
     * @param non-negative-int $reserved1
     */
    public function __construct(
        public readonly string $exchange,
        public readonly string $exchangeType,
        public readonly bool $passive = false,
        public readonly bool $durable = false,
        public readonly bool $autoDelete = false,
        public readonly bool $internal = false,
        public readonly bool $noWait = false,
        public readonly array $arguments = [],
        public readonly int $reserved1 = 0,
    ) {}

    public static function read(Io\ReadBytes $reader): Frame
    {
        $reserved1 = $reader->readUint16();

        $exchange = $reader->readString();
        \assert($exchange !== '', 'exchange must not be empty.');

        $exchangeType = $reader->readString();
        \assert($exchangeType !== '', 'exchange type must not be empty.');

        [$passive, $durable, $autoDelete, $internal, $noWait] = $reader->readBits(5);
        $arguments = $reader->readTable();

        return new self(
            $exchange,
            $exchangeType,
            $passive,
            $durable,
            $autoDelete,
            $internal,
            $noWait,
            $arguments,
            $reserved1,
        );
    }

    public function write(Io\WriteBytes $writer): void
    {
        $writer
            ->writeUint16($this->reserved1)
            ->writeString($this->exchange)
            ->writeString($this->exchangeType)
            ->writeBits($this->passive, $this->durable, $this->autoDelete, $this->internal, $this->noWait)
            ->writeTable($this->arguments);
    }
}
