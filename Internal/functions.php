<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal;

use Typhoon\Amqp091\Internal\Protocol\Type;
use Typhoon\ByteOrder\ReadFrom;
use Typhoon\Endian\endian;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 * @param non-empty-string $query
 * @return array<non-empty-string, non-empty-string|non-empty-list<non-empty-string>>
 */
function parseQuery(string $query): array
{
    $items = [];

    foreach (queryIterator($query) as $name => $value) {
        if (!isset($items[$name])) {
            $items[$name] = $value;
        } else {
            if (!\is_array($items[$name])) {
                $items[$name] = [$items[$name]];
            }

            $items[$name][] = $value;
        }
    }

    return $items;
}

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 * @param non-empty-string $query
 * @return \Generator<non-empty-string, non-empty-string>
 */
function queryIterator(string $query): \Generator
{
    $pairs = explode('&', $query);

    foreach ($pairs as $pair) {
        $it = explode('=', $pair, 2);

        if (\count($it) === 2) {
            [$name, $value] = [$it[0], urldecode($it[1])];

            if ($name !== '' && $value !== '') {
                yield $name => $value;
            }
        }
    }
}

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 * @return array{\DateTimeInterface|array|string|int|float|bool|null, int}
 * @throws \Throwable
 */
function readValue(ReadFrom $reader, endian $endian = endian::network): array
{
    $type = Type::from($reader->readUint8($endian));

    [$value, $len] = match ($type) {
        Type::boolean => [$reader->readUint8($endian) > 0, 1],
        Type::int8 => [$reader->readInt8($endian), 1],
        Type::uint8 => [$reader->readUint8($endian), 1],
        Type::int16 => [$reader->readInt16($endian), 2],
        Type::uint16 => [$reader->readUint16($endian), 2],
        Type::int32 => [$reader->readInt32($endian), 4],
        Type::uint32 => [$reader->readUint32($endian), 4],
        Type::int64 => [$reader->readInt64($endian), 8],
        Type::uint64 => [$reader->readUint64($endian), 8],
        Type::float => [$reader->readFloat($endian), 4],
        Type::double => [$reader->readDouble($endian), 8],
        Type::decimal => [readDecimal($reader, $endian), 5],
        Type::string => readString($reader, $endian),
        Type::text => readText($reader, $endian),
        Type::timestamp => [readTimestamp($reader, $endian), 8],
        Type::array => readArray($reader, $endian),
        Type::table => readTable($reader, $endian),
        Type::null => [null, 0],
    };

    return [$value, $len + 1];
}

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 * @return array{string, int}
 * @throws \Throwable
 */
function readString(ReadFrom $reader, endian $endian = endian::network): array
{
    $v = '';
    if (($size = $reader->readUint8($endian)) > 0) {
        $v = $reader->read($size);
    }

    return [$v, \strlen($v) + 1];
}

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 * @return array{string, int}
 * @throws \Throwable
 */
function readText(ReadFrom $reader, endian $endian = endian::network): array
{
    $v = '';
    if (($size = $reader->readUint32($endian)) > 0) {
        $v = $reader->read($size);
    }

    return [$v, \strlen($v) + 4];
}

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 * @throws \Throwable
 */
function readDecimal(ReadFrom $reader, endian $endian = endian::network): int
{
    $scale = $reader->readUint8($endian);
    $value = $reader->readUint32($endian);

    return $value * (10 ** $scale);
}

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 * @throws \Throwable
 */
function readTimestamp(ReadFrom $reader, endian $endian = endian::network): \DateTimeInterface
{
    return new \DateTimeImmutable(\sprintf('@%s', $reader->readUint64($endian)));
}

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 * @return array{list<\DateTimeInterface|array|string|int|float|bool|null>, int}
 * @throws \Throwable
 */
function readArray(ReadFrom $reader, endian $endian = endian::network): array
{
    $size = $cursor = $reader->readUint32($endian);
    $values = [];

    while ($cursor > 0) {
        [$value, $n] = readValue($reader, $endian);
        $cursor -= $n;

        $values[] = $value;
    }

    return [$values, $size + 4];
}

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 * @return array{array<string, \DateTimeInterface|array|int|string|float|bool|null>, int}
 * @throws \Throwable
 */
function readTable(ReadFrom $reader, endian $endian = endian::network): array
{
    $size = $cursor = $reader->readUint32($endian);
    $table = [];

    while ($cursor > 0) {
        [$key, $n] = readString($reader, $endian);
        $cursor -= $n;

        [$value, $n] = readValue($reader, $endian);
        $cursor -= $n;

        $table[$key] = $value;
    }

    return [$table, $size + 4];
}
