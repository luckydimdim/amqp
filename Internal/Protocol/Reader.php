<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol;

use Typhoon\Amqp091\Exception\UnsupportedClassMethod;
use Typhoon\Amqp091\Internal\Protocol\Frame\ConnectionStart;
use Typhoon\ByteBuffer\Buffer;
use Typhoon\ByteOrder\ReaderWriter;
use Typhoon\ByteOrder\ReadFrom;
use Typhoon\Endian\endian;
use Typhoon\StringBuffer\Str;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class Reader
{
    /** @var int */
    private const HEADER_SIZE = 7;

    /** @var array<ClassType::*, array<ClassMethod::*, class-string<Frame>>> */
    private const METHODS = [
        ClassType::CONNECTION => [
            ClassMethod::CONNECTION_START => ConnectionStart::class,
        ],
    ];

    private readonly endian $endian;

    private readonly Buffer $buffer;

    private readonly ReaderWriter $rw;

    private readonly Parser $parser;

    public function __construct(
        private readonly ReadFrom $reader,
    ) {
        $this->buffer = new Buffer();
        $this->endian = endian::network;
        $this->rw = new ReaderWriter(
            $this->buffer,
            $this->buffer,
        );
        $this->parser = new Parser(
            $this->endian,
            $this->rw,
        );
    }

    /**
     * @return iterable<array-key, MethodFrame>
     * @throws \Throwable
     */
    public function readFrames(): iterable
    {
        $header = Str::immutable($this->reader->read(self::HEADER_SIZE));

        $type = FrameType::from($this->endian->unpackUint8($header[0]));
        $channel = $this->endian->unpackUint16($header['1:3']);

        if (($size = $this->endian->unpackUint32($header['3:7'])) > 0) {
            $this->rw->write($this->reader->read($size));
        }

        yield match ($type) {
            FrameType::method => $this->parseMethodFrame($channel),
            default => throw new \Exception('Not implemented yet'),
        };

        if ($this->reader->readUint8($this->endian) !== 206) {
            throw new \Exception('Bad frame.');
        }
    }

    /**
     * @throws \Throwable
     */
    private function parseMethodFrame(int $channelId): MethodFrame
    {
        $classId = $this->rw->readUint16($this->endian);
        $methodId = $this->rw->readUint16($this->endian);

        $frame = (self::METHODS[$classId][$methodId] ?? throw UnsupportedClassMethod::forClassMethod($classId, $methodId))::parse($this->parser);

        return new MethodFrame($channelId, $frame);
    }
}
