<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Connection;

use Amp\Future;
use Amp\Socket\Socket;
use Revolt\EventLoop;
use Typhoon\AmpBridge\AmpReaderWriter;
use Typhoon\ByteBuffer\BufferedReaderWriter;
use Typhoon\ByteOrder\ReaderWriter;
use Typhoon\Amqp091\Internal\Protocol;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class AmqpConnection
{
    private readonly Socket $socket;

    private readonly Hooks $hooks;

    public function __construct(Socket $socket)
    {
        $this->socket = $socket;

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
     * @param non-empty-string $bytes
     */
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
