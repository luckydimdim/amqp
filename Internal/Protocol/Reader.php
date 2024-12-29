<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol;

use Typhoon\Amqp091\Exception\UnsupportedClassMethod;
use Typhoon\Amqp091\Internal\Io;
use Typhoon\Amqp091\Internal\Protocol\Frame\ChannelOpenOkFrame;
use Typhoon\Amqp091\Internal\Protocol\Frame\ConnectionOpenOk;
use Typhoon\Amqp091\Internal\Protocol\Frame\ConnectionStart;
use Typhoon\Amqp091\Internal\Protocol\Frame\ConnectionTune;
use Typhoon\Amqp091\Internal\Protocol\Frame\ExchangeDeclareOk;
use Typhoon\Amqp091\Internal\Protocol\Frame\QueueBindOk;
use Typhoon\Amqp091\Internal\Protocol\Frame\QueueDeclareOk;
use Typhoon\Amqp091\Internal\Protocol\Frame\QueueDeleteOk;
use Typhoon\Amqp091\Internal\Protocol\Frame\QueuePurgeOk;
use Typhoon\Amqp091\Internal\Protocol\Frame\QueueUnbindOk;
use Typhoon\ByteOrder\ReadFrom;

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
            ClassMethod::CONNECTION_TUNE => ConnectionTune::class,
            ClassMethod::CONNECTION_OPEN_OK => ConnectionOpenOk::class,
        ],
        ClassType::CHANNEL => [
            ClassMethod::CHANNEL_OPEN_OK => ChannelOpenOkFrame::class,
        ],
        ClassType::EXCHANGE => [
            ClassMethod::EXCHANGE_DECLARE_OK => ExchangeDeclareOk::class,
        ],
        ClassType::QUEUE => [
            ClassMethod::QUEUE_DECLARE_OK => QueueDeclareOk::class,
            ClassMethod::QUEUE_BIND_OK => QueueBindOk::class,
            ClassMethod::QUEUE_UNBIND_OK => QueueUnbindOk::class,
            ClassMethod::QUEUE_PURGE_OK => QueuePurgeOk::class,
            ClassMethod::QUEUE_DELETE_OK => QueueDeleteOk::class,
        ],
    ];

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
            FrameType::method => $this->parseMethodFrame($channelId),
            default => throw new \Exception('Not implemented yet'),
        };

        if ($this->reader->readUint8() !== 206) {
            throw new \Exception('Bad frame.');
        }
    }

    /**
     * @param non-negative-int $channelId
     * @throws \Throwable
     */
    private function parseMethodFrame(int $channelId): Request
    {
        $classId = $this->buffer->readUint16();
        $methodId = $this->buffer->readUint16();

        $frame = (self::METHODS[$classId][$methodId] ?? throw UnsupportedClassMethod::forClassMethod($classId, $methodId))::read($this->buffer);

        return new Request($channelId, $frame);
    }
}
