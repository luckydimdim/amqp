<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Io;

use Amp;
use Amp\Future;
use Amp\Socket\Socket;
use Revolt\EventLoop;
use Typhoon\AmpBridge\AmpReaderWriter;
use Typhoon\Amqp091\Internal\Hooks;
use Typhoon\Amqp091\Internal\Protocol;
use Typhoon\ByteBuffer\BufferedReaderWriter;
use Typhoon\ByteOrder\ReaderWriter;
use Typhoon\ByteWriter\Writer;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class AmqpConnection implements Writer
{
    private readonly Socket $socket;

    private readonly Hooks $hooks;

    private readonly Buffer $buffer;

    private ?string $heartbeatId = null;

    private float $lastWrite = 0;

    public function __construct(Socket $socket)
    {
        $this->socket = $socket;
        $this->buffer = Buffer::alloc();

        $hooks = new Hooks();
        $this->hooks = $hooks;

        EventLoop::queue(static function () use ($socket, $hooks): void {
            $reader = new Protocol\Reader(
                new ReaderWriter(
                    new BufferedReaderWriter(
                        new AmpReaderWriter($socket),
                    ),
                ),
            );

            try {
                while (($frames = $reader->iterate()) !== []) {
                    $hooks->emit(...$frames);
                }
            } catch (\Throwable $e) {
                $hooks->error($e);
            }

            $hooks->complete();
        });
    }

    /**
     * @template T of Protocol\Frame
     * @param non-negative-int $channelId
     * @param class-string<T> $frameType
     * @return Future<T>
     */
    public function subscribe(int $channelId, string $frameType): Future
    {
        return $this->hooks->subscribe($channelId, $frameType);
    }

    /**
     * @template T of Protocol\Frame
     * @param non-negative-int $channelId
     * @param class-string<T> ...$frameTypes
     * @return Future<T>
     */
    public function subscribeAny(int $channelId, string ...$frameTypes): Future
    {
        return $this->hooks->subscribeAny($channelId, ...$frameTypes);
    }

    /**
     * @param non-negative-int $channelId
     */
    public function unsubscribe(int $channelId): void
    {
        $this->hooks->unsubscribe($channelId);
    }

    /**
     * @param iterable<array-key, Protocol\Frame>|Protocol\Frame $frames
     * @throws \Throwable
     */
    public function writeFrame(iterable|Protocol\Frame $frames): void
    {
        if (!is_iterable($frames)) {
            $frames = [$frames];
        }

        foreach ($frames as $frame) {
            $frame->write($this->buffer);
        }

        $this->buffer->writeTo($this);
    }

    public function write(string $bytes): void
    {
        $this->socket->write($bytes);
        $this->lastWrite = Amp\now();
    }

    /**
     * @param non-negative-int $interval
     */
    public function heartbeat(int $interval): void
    {
        $interval = (int) ($interval / 2);

        $this->heartbeatId = EventLoop::repeat((int) ($interval / 3), function () use ($interval): void {
            if (Amp\now() >= ($this->lastWrite + $interval)) {
                $this->writeFrame(Protocol\Heartbeat::frame);
            }
        });
    }

    public function close(): void
    {
        if (!$this->socket->isClosed()) {
            $this->socket->close();
        }

        if ($this->heartbeatId !== null) {
            EventLoop::cancel($this->heartbeatId);
        }
    }
}
