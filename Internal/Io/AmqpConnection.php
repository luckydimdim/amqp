<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Io;

use Amp\Future;
use Amp\Socket\Socket;
use Revolt\EventLoop;
use Typhoon\AmpBridge\AmpReaderWriter;
use Typhoon\Amqp091\Internal\Hooks;
use Typhoon\Amqp091\Internal\Protocol;
use Typhoon\Amqp091\Internal\WriterTo;
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
     * @throws \Throwable
     */
    public function writeFrame(Protocol\Frame $frame): void
    {
        $frame
            ->write($this->buffer)
            ->writeTo($this);
    }

    public function writeAt(WriterTo $to): void
    {
        $to->writeTo($this);
    }

    public function write(string $bytes): void
    {
        $this->socket->write($bytes);
    }

    public function close(): void
    {
        if (!$this->socket->isClosed()) {
            $this->socket->close();
        }
    }
}
