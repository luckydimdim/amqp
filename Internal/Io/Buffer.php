<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Io;

use Typhoon\Amqp091\Exception\UnknownValueType;
use Typhoon\Amqp091\Internal\Protocol\Type;
use Typhoon\ByteWriter\Writer;
use Typhoon\Endian\endian;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class Buffer implements
    WriteBytes,
    ReadBytes,
    WriterTo,
    \Countable
{
    private const DEFAULT_BUFFER_SIZE = 4096;

    private string $buffer;

    /** @var non-negative-int */
    private int $writeCursor = 0;

    /** @var non-negative-int */
    private int $readCursor = 0;

    /** @var non-negative-int */
    private int $len;

    /** @var non-negative-int */
    private int $allocSize;

    private readonly endian $endian;

    /**
     * @param non-negative-int $size
     */
    public static function alloc(
        int $size = self::DEFAULT_BUFFER_SIZE,
        endian $endian = endian::network,
    ): self {
        return new self(str_repeat("\0", $size), $endian);
    }

    public static function empty(endian $endian = endian::network): self
    {
        return new self(endian: $endian);
    }

    private function __construct(string $buffer = '', endian $endian = endian::network)
    {
        $this->endian = $endian;
        $this->buffer = $buffer;
        $this->len = \strlen($buffer);
        $this->allocSize = $this->len ?: self::DEFAULT_BUFFER_SIZE;
    }

    public function writeInt8(int $v): self
    {
        return $this->append($this->endian->packInt8($v));
    }

    public function writeUint8(int $v): self
    {
        return $this->append($this->endian->packUint8($v));
    }

    public function writeInt16(int $v): self
    {
        return $this->append($this->endian->packInt16($v));
    }

    public function writeUint16(int $v): self
    {
        return $this->append($this->endian->packUint16($v));
    }

    public function writeInt32(int $v): self
    {
        return $this->append($this->endian->packInt32($v));
    }

    public function writeUint32(int $v): self
    {
        return $this->append($this->endian->packUint32($v));
    }

    public function writeInt64(int $v): self
    {
        return $this->append($this->endian->packInt64($v));
    }

    public function writeUint64(int $v): self
    {
        return $this->append($this->endian->packUint64($v));
    }

    public function writeFloat(float $v): self
    {
        return $this->append($this->endian->packFloat($v));
    }

    public function writeDouble(float $v): self
    {
        return $this->append($this->endian->packDouble($v));
    }

    public function writeString(string $v): self
    {
        $this
            ->writeUint8(\strlen($v))
            ->write($v);

        return $this;
    }

    public function writeText(string $v): self
    {
        $this
            ->writeUint32(\strlen($v))
            ->write($v);

        return $this;
    }

    public function writeTimestamp(\DateTimeInterface $date): self
    {
        $timestamp = $date->getTimestamp();
        \assert($timestamp >= 0);

        return $this->writeUint64($timestamp);
    }

    public function writeTable(array $values): self
    {
        return $this->reserve($this->endian->packUint32(...), static function (WriteBytes $buffer) use ($values): void {
            foreach ($values as $key => $value) {
                $buffer = $buffer
                    ->writeString((string) $key)
                    ->writeValue($value);
            }
        });
    }

    public function writeArray(array $values): self
    {
        return $this->reserve($this->endian->packUint32(...), static function (WriteBytes $buffer) use ($values): void {
            foreach ($values as $value) {
                $buffer = $buffer->writeValue($value);
            }
        });
    }

    public function writeValue(mixed $value): self
    {
        return match (true) {
            \is_string($value) => $this
                ->writeUint8(Type::text->value)
                ->writeText($value),
            \is_int($value) => $this
                ->writeUint8(Type::int32->value)
                ->writeInt32($value),
            \is_float($value) => $this
                ->writeUint8(Type::float->value)
                ->writeDouble($value),
            \is_bool($value) => $this
                ->writeUint8(Type::boolean->value)
                ->writeUint8((int) $value),
            $value instanceof \DateTimeInterface => $this
                ->writeUint8(Type::timestamp->value)
                ->writeTimestamp($value),
            $value === null => $this->writeUint8(Type::null->value),
            \is_array($value) && array_is_list($value) => $this
                ->writeUint8(Type::array->value)
                ->writeArray($value),
            \is_array($value) => $this
                ->writeUint8(Type::table->value)
                ->writeTable($value),
            default => throw UnknownValueType::forValue($value),
        };
    }

    public function writeBits(bool ...$bits): self
    {
        $value = 0;

        foreach ($bits as $i => $bit) {
            /** @var non-negative-int */
            $value |= (int) $bit << (int) $i;
        }

        return $this->writeUint8($value);
    }

    public function write(string $v): self
    {
        return $this->append($v);
    }

    public function writeTo(Writer $writer): void
    {
        if (($v = $this->reset()) !== '') {
            $writer->write($v);
        }
    }

    public function readInt8(): int
    {
        return $this->endian->unpackInt8($this->consume(1));
    }

    public function readUint8(): int
    {
        return $this->endian->unpackUint8($this->consume(1));
    }

    public function readInt16(): int
    {
        return $this->endian->unpackInt16($this->consume(2));
    }

    public function readUint16(): int
    {
        return $this->endian->unpackUint16($this->consume(2));
    }

    public function readInt32(): int
    {
        return $this->endian->unpackInt32($this->consume(4));
    }

    public function readUint32(): int
    {
        return $this->endian->unpackUint32($this->consume(4));
    }

    public function readInt64(): int
    {
        return $this->endian->unpackInt64($this->consume(8));
    }

    public function readUint64(): int
    {
        return $this->endian->unpackUint64($this->consume(8));
    }

    public function readFloat(): float
    {
        return $this->endian->unpackFloat($this->consume(4));
    }

    public function readDouble(): float
    {
        return $this->endian->unpackDouble($this->consume(8));
    }

    public function readTimestamp(): \DateTimeInterface
    {
        return new \DateTimeImmutable(\sprintf('@%s', $this->readUint64()));
    }

    public function readDecimal(): int
    {
        $scale = $this->readUint8();
        $value = $this->readUint32();

        return (int) ($value * (10 ** $scale));
    }

    public function readText(): string
    {
        $v = '';
        if (($size = $this->readUint32()) > 0) {
            $v = $this->read($size);
        }

        return $v;
    }

    public function readString(): string
    {
        $v = '';
        if (($size = $this->readUint8()) > 0) {
            $v = $this->read($size);
        }

        return $v;
    }

    public function readArray(): array
    {
        $cursor = $this->readCursor + $this->readUint32();
        $values = [];

        while ($cursor > $this->readCursor) {
            $values[] = $this->readValue();
        }

        return $values;
    }

    public function readTable(): array
    {
        $cursor = $this->readCursor + $this->readUint32();
        $table = [];

        while ($cursor > $this->readCursor) {
            $table[$this->readString()] = $this->readValue();
        }

        return $table;
    }

    public function read(int $n): string
    {
        return $this->consume($n);
    }

    public function readValue(): mixed
    {
        return match (Type::from($this->readUint8())) {
            Type::boolean => $this->readUint8() > 0,
            Type::int8 => $this->readInt8(),
            Type::uint8 => $this->readUint8(),
            Type::int16 => $this->readInt16(),
            Type::uint16 => $this->readUint16(),
            Type::int32 => $this->readInt32(),
            Type::uint32 => $this->readUint32(),
            Type::int64 => $this->readInt64(),
            Type::uint64 => $this->readUint64(),
            Type::float => $this->readFloat(),
            Type::double => $this->readDouble(),
            Type::decimal => $this->readDecimal(),
            Type::string => $this->readString(),
            Type::text => $this->readText(),
            Type::timestamp => $this->readTimestamp(),
            Type::array => $this->readArray(),
            Type::table => $this->readTable(),
            Type::null => null,
        };
    }

    public function readBits(int $n): array
    {
        /** @var non-empty-list<bool> $bits */
        $bits = [];
        $value = $this->readUint8();

        for ($i = 0; $i < $n; ++$i) {
            $bits[] = ($value & (1 << $i)) > 0;
        }

        return $bits;
    }

    public function reset(): string
    {
        [$v, $this->writeCursor, $this->readCursor] = [substr($this->buffer, 0, $this->writeCursor), 0, 0];

        return $v;
    }

    public function reserve(callable $reserve, callable $write): self
    {
        $pos = $this->writeCursor;
        $this->writeCursor += $idle = \strlen($reserve(0));
        $write($this);

        $len = $this->writeCursor - $pos - $idle;
        \assert($len >= 0);
        $v = $reserve($len);

        for ($i = 0, $cursor = $pos; $i < \strlen($v); ++$i, ++$cursor) {
            $this->buffer[$cursor] = $v[$i];
        }

        return $this;
    }

    public function count(): int
    {
        return $this->writeCursor;
    }

    public function rewind(): self
    {
        $this->readCursor = 0;

        return $this;
    }

    private function append(string $v): self
    {
        $valueLen = \strlen($v);

        if ($this->len < ($this->writeCursor + $valueLen)) {
            $allocSize = max($this->allocSize, $valueLen);
            $this->buffer .= str_repeat("\0", $allocSize);
            $this->len += $allocSize;
        }

        for ($i = 0; $i < \strlen($v); ++$i) {
            $this->buffer[$this->writeCursor++] = $v[$i];
        }

        return $this;
    }

    /**
     * @param positive-int $n
     * @return non-empty-string
     */
    private function consume(int $n): string
    {
        if (($this->readCursor + $n) > $this->len) {
            throw new \RuntimeException('Buffer is empty.');
        }

        /** @var non-empty-string $v */
        $v = substr($this->buffer, $this->readCursor, $n);
        $this->readCursor += $n;
        $this->writeCursor = max($this->writeCursor - $n, 0);

        return $v;
    }
}
