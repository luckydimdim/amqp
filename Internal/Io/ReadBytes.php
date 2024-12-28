<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Io;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
interface ReadBytes
{
    public function readInt8(): int;

    /**
     * @return non-negative-int
     */
    public function readUint8(): int;

    public function readInt16(): int;

    /**
     * @return non-negative-int
     */
    public function readUint16(): int;

    public function readInt32(): int;

    /**
     * @return non-negative-int
     */
    public function readUint32(): int;

    public function readInt64(): int;

    /**
     * @return non-negative-int
     */
    public function readUint64(): int;

    public function readFloat(): float;

    public function readDouble(): float;

    /**
     * @throws \Throwable
     */
    public function readTimestamp(): \DateTimeInterface;

    public function readDecimal(): int;

    public function readText(): string;

    public function readString(): string;

    /**
     * @return list<\DateTimeInterface|array|string|int|float|bool|null>
     */
    public function readArray(): array;

    /**
     * @return array<string, \DateTimeInterface|array|int|string|float|bool|null>
     */
    public function readTable(): array;

    /**
     * @param positive-int $n
     * @return non-empty-string
     */
    public function read(int $n): string;

    /**
     * @return \DateTimeInterface|array|string|int|float|bool|null
     */
    public function readValue(): mixed;

    /**
     * @param non-negative-int $n
     * @return list<bool>
     */
    public function readBits(int $n): array;

    /**
     * @param positive-int $limit
     */
    public function discard(int $limit): self;
}
