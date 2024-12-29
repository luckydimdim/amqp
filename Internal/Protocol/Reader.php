<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol;

use Typhoon\Amqp091\Internal\Io;
use Typhoon\ByteOrder\ReadFrom;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class Reader
{
    /** @var int */
    private const HEADER_SIZE = 7;

    private readonly ReadFrom $reader;

    private readonly Io\Buffer $buffer;

    public function __construct(ReadFrom $reader)
    {
        $this->reader = $reader;
        $this->buffer = Io\Buffer::alloc();
    }

    /**
     * @return iterable<array-key, Request>
     * @throws \Throwable
     */
    public function iterate(): iterable
    {
        $this->buffer
            ->write($this->reader->read(self::HEADER_SIZE))
            ->rewind();

        $type = FrameType::from($this->buffer->readUint8());
        $channelId = $this->buffer->readUint16();

        if (($size = $this->buffer->readUint32()) > 0) {
            $this->buffer
                ->write($this->reader->read($size))
                ->rewind();
        }

        yield match ($type) {
            FrameType::method => Protocol::amqp091->parseMethod($this->buffer, $channelId),
            default => throw new \Exception('Not implemented yet'),
        };

        if ($this->reader->readUint8() !== Protocol::FRAME_END) {
            throw new \Exception('Bad frame.');
        }
    }
}
