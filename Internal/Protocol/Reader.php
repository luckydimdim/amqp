<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol;

use Amp\Cancellation;
use Typhoon\Amqp091\Exception\FrameIsBroken;
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
        $this->buffer = Io\Buffer::empty();
    }

    /**
     * @throws \Throwable
     */
    public function read(?Cancellation $cancellation = null): Request
    {
        $this->buffer->write($this->reader->read(self::HEADER_SIZE, $cancellation));

        $type = FrameType::from($this->buffer->readUint8());
        $channelId = $this->buffer->readUint16();

        if (($size = $this->buffer->readUint32()) > 0) {
            $this->buffer->write($this->reader->read($size, $cancellation));
        }

        $frame = match ($type) {
            FrameType::method => Protocol::amqp091->parseMethod($this->buffer),
            FrameType::header => Protocol::amqp091->parseHeader($this->buffer),
            FrameType::body => Protocol::amqp091->parseBody($this->buffer),
            FrameType::heartbeat => Heartbeat::frame,
        };

        if ($this->reader->readUint8() !== Protocol::FRAME_END) {
            throw new FrameIsBroken();
        }

        return new Request($channelId, $frame);
    }
}
