<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Io;

use Amp;
use Amp\Socket\Socket;
use Revolt\EventLoop;
use Typhoon\AmpBridge\AmpReaderWriter;
use Typhoon\Amqp091\Exception\ConnectionIsClosed;
use Typhoon\Amqp091\Internal\Hooks;
use Typhoon\Amqp091\Internal\Protocol;
use Typhoon\ByteBuffer\BufferedReaderWriter;
use Typhoon\ByteOrder\ReaderWriter;
use Typhoon\ByteReader\ReaderIsClosed;
use Typhoon\ByteWriter\Writer;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class AmqpConnection implements Writer
{
    private readonly Socket $socket;

    private readonly Buffer $buffer;

    private ?string $heartbeatId = null;

    private float $lastWrite = 0;

    /** @psalm-suppress UnusedProperty used by reference. */
    private bool $isClosed = false;

    public function __construct(Socket $socket, Hooks $hooks)
    {
        $this->socket = $socket;
        $this->buffer = Buffer::alloc();
        $isClosed = &$this->isClosed;

        EventLoop::queue(static function () use ($socket, $hooks, &$isClosed): void {
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
                $e = match (true) {
                    $e instanceof ReaderIsClosed => new ConnectionIsClosed(),
                    default => $e,
                };

                if (!$isClosed) {
                    $hooks->error($e);
                }
            }

            $hooks->complete();
        });
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

        $this->isClosed = true;
    }
}
