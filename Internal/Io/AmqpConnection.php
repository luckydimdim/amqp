<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Io;

use Amp;
use Amp\Pipeline\ConcurrentIterator;
use Amp\Pipeline\Queue;
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

    private bool $isClosed = false;

    public function __construct(Socket $socket, Hooks $hooks)
    {
        $this->socket = $socket;
        $this->buffer = Buffer::alloc();

        /** @var Queue<Protocol\Request> $queue */
        $queue = new Queue();

        $this->parseRequests($queue);
        $this->dispatchRequests($queue->iterate(), $hooks);
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

    /**
     * @param Queue<Protocol\Request> $queue
     */
    private function parseRequests(Queue $queue): void
    {
        $socket = &$this->socket;
        $isClosed = &$this->isClosed;

        EventLoop::queue(static function () use (&$socket, &$isClosed, $queue): void {
            $reader = new Protocol\Reader(
                new ReaderWriter(
                    new BufferedReaderWriter(
                        new AmpReaderWriter($socket),
                    ),
                ),
            );

            try {
                while (($request = $reader->read()) !== null) {
                    $queue
                        ->pushAsync($request)
                        ->await();
                }
            } catch (\Throwable $e) {
                $e = match (true) {
                    $e instanceof ReaderIsClosed => new ConnectionIsClosed(),
                    default => $e,
                };

                if (!$isClosed) {
                    $queue->error($e);
                }
            }

            $queue->complete();
        });
    }

    /**
     * @param ConcurrentIterator<Protocol\Request> $iterator
     */
    private function dispatchRequests(ConcurrentIterator $iterator, Hooks $hooks): void
    {
        $isClosed = &$this->isClosed;

        EventLoop::queue(static function () use (&$isClosed, $iterator, $hooks): void {
            while (!$isClosed) {
                if ($iterator->continue()) {
                    $hooks->emit($iterator->getValue());
                }
            }
        });
    }
}
