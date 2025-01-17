<?php

declare(strict_types=1);

namespace Thesis\Amqp\Internal\Protocol;

/**
 * @internal
 */
enum Type: int
{
    case boolean = 0x74;
    case int8 = 0x62;
    case uint8 = 0x42;
    case int16 = 0x55;
    case uint16 = 0x75;
    case int32 = 0x49;
    case uint32 = 0x69;
    case int64 = 0x4C;
    case uint64 = 0x6C;
    case float = 0x66;
    case double = 0x64;
    case decimal = 0x44;
    case string = 0x73;
    case text = 0x53;
    case array = 0x41;
    case timestamp = 0x54;
    case table = 0x46;
    case null = 0x56;
}
