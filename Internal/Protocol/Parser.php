<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol;

use Typhoon\ByteOrder\ReadFrom;
use Typhoon\Endian\endian;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class Parser
{
    public function __construct(
        private readonly endian $endian,
        private readonly ReadFrom $reader,
    ) {}

    /**
     * @throws \Throwable
     */
    public function readUint8(): int
    {
        return $this->reader->readUint8($this->endian);
    }

    /**
     * @throws \Throwable
     */
    public function readTimestamp(): \DateTimeInterface
    {
        return new \DateTimeImmutable(\sprintf('@%s', $this->reader->readUint64($this->endian)));
    }

    /**
     * @throws \Throwable
     */
    public function readDecimal(): int
    {
        $scale = $this->readUint8();
        $value = $this->reader->readUint32($this->endian);

        return $value * (10 ** $scale);
    }

    /**
     * @throws \Throwable
     */
    public function readText(): string
    {
        [$text] = $this->doReadText();

        return $text;
    }

    /**
     * @throws \Throwable
     */
    public function readString(): string
    {
        [$string] = $this->doReadString();

        return $string;
    }

    /**
     * @return list<\DateTimeInterface|array|string|int|float|bool|null>
     * @throws \Throwable
     */
    public function readArray(): array
    {
        [$array] = $this->doReadArray();

        return $array;
    }

    /**
     * @return array<string, \DateTimeInterface|array|int|string|float|bool|null>
     * @throws \Throwable
     */
    public function readTable(): array
    {
        [$table] = $this->doReadTable();

        return $table;
    }

    /**
     * @return array{string, int}
     * @throws \Throwable
     */
    private function doReadText(): array
    {
        $v = '';
        if (($size = $this->reader->readUint32($this->endian)) > 0) {
            $v = $this->reader->read($size);
        }

        return [$v, \strlen($v) + 4];
    }

    /**
     * @return array{string, int}
     * @throws \Throwable
     */
    private function doReadString(): array
    {
        $v = '';
        if (($size = $this->readUint8()) > 0) {
            $v = $this->reader->read($size);
        }

        return [$v, \strlen($v) + 1];
    }

    /**
     * @return array{list<\DateTimeInterface|array|string|int|float|bool|null>, int}
     * @throws \Throwable
     */
    private function doReadArray(): array
    {
        $size = $cursor = $this->reader->readUint32($this->endian);
        $values = [];

        while ($cursor > 0) {
            [$value, $n] = $this->readValue();
            $cursor -= $n;

            $values[] = $value;
        }

        return [$values, $size + 4];
    }

    /**
     * @return array{array<string, \DateTimeInterface|array|int|string|float|bool|null>, int}
     * @throws \Throwable
     */
    private function doReadTable(): array
    {
        $size = $cursor = $this->reader->readUint32($this->endian);
        $table = [];

        while ($cursor > 0) {
            [$key, $n] = $this->doReadString();
            $cursor -= $n;

            [$value, $n] = $this->readValue();
            $cursor -= $n;

            $table[$key] = $value;
        }

        return [$table, $size + 4];
    }

    /**
     * @return array{\DateTimeInterface|array|string|int|float|bool|null, int}
     * @throws \Throwable
     */
    private function readValue(): array
    {
        $type = Type::from($this->readUint8());

        [$value, $len] = match ($type) {
            Type::boolean => [$this->readUint8() > 0, 1],
            Type::int8 => [$this->reader->readInt8($this->endian), 1],
            Type::uint8 => [$this->readUint8(), 1],
            Type::int16 => [$this->reader->readInt16($this->endian), 2],
            Type::uint16 => [$this->reader->readUint16($this->endian), 2],
            Type::int32 => [$this->reader->readInt32($this->endian), 4],
            Type::uint32 => [$this->reader->readUint32($this->endian), 4],
            Type::int64 => [$this->reader->readInt64($this->endian), 8],
            Type::uint64 => [$this->reader->readUint64($this->endian), 8],
            Type::float => [$this->reader->readFloat($this->endian), 4],
            Type::double => [$this->reader->readDouble($this->endian), 8],
            Type::decimal => [$this->readDecimal(), 5],
            Type::string => $this->doReadString(),
            Type::text => $this->doReadText(),
            Type::timestamp => [$this->readTimestamp(), 8],
            Type::array => $this->doReadArray(),
            Type::table => $this->doReadTable(),
            Type::null => [null, 0],
        };

        return [$value, $len + 1];
    }
}
