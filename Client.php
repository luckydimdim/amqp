<?php

declare(strict_types=1);

namespace Typhoon\Amqp091;

use Amp\Cancellation;
use Amp\CancelledException;
use Amp\NullCancellation;
use Amp\Socket;
use Typhoon\Amqp091\Internal\Connection\AmqpConnection;
use Typhoon\Amqp091\Internal\Io;
use Typhoon\Amqp091\Internal\Protocol;
use Typhoon\Amqp091\Internal\Protocol\Frame;
use Typhoon\Amqp091\Internal\Protocol\Frame\ChannelOpenOkFrame;
use Typhoon\Amqp091\Internal\Protocol\Frame\ConnectionOpenOk;
use Typhoon\Amqp091\Internal\Protocol\Frame\ConnectionStart;
use Typhoon\Amqp091\Internal\Protocol\Frame\ConnectionTune;
use Typhoon\Amqp091\Internal\VersionProvider;

/**
 * @api
 * @psalm-type ClientProperties = array{product: non-empty-string, version: non-empty-string, platform: non-empty-string, capabilities: array<non-empty-string, bool>}
 */
final class Client
{
    private ?AmqpConnection $connection = null;

    /** @var non-negative-int */
    private int $channelId = 1;

    /** @var array<non-negative-int, Channel> */
    private array $channels = [];

    /** @var ClientProperties */
    private array $properties;

    private readonly Io\Buffer $buffer;

    public function __construct(private readonly Uri $uri)
    {
        $this->properties = [
            'product' => 'AMQP 0.9.1 Client',
            'version' => VersionProvider::provide(),
            'platform' => 'php',
            'capabilities' => [
                'connection.blocked' => true,
                'basic.nack' => true,
                'publisher_confirms' => true,
            ],
        ];

        $this->buffer = Io\Buffer::alloc();
    }

    /**
     * @throws Socket\ConnectException
     * @throws CancelledException
     */
    public function connect(Cancellation $cancellation = new NullCancellation()): void
    {
        if ($this->connection === null) {
            $this->connection = new AmqpConnection(
                Socket\connect($this->uri->connectionDsn()),
            );

            $this->connection->writeAt(Frame\ProtocolHeader::frame->write($this->buffer));

            $this->connectionStart(
                $this->await(ConnectionStart::class, cancellation: $cancellation),
            );

            $this->connectionTune(
                $this->await(ConnectionTune::class, cancellation: $cancellation),
            );

            $this->connectionOpen($cancellation);
        }
    }

    public function channel(Cancellation $cancellation = new NullCancellation()): Channel
    {
        $channelId = $this->allocateChannelId();
        $this->openChannel($channelId, $cancellation);

        return new Channel($channelId, $this->connection ?: throw new \LogicException('Connection is closed.'));
    }

    private function connectionStart(ConnectionStart $start): void
    {
        $this->write(Protocol\Method::connectionStartOk($start->serverProperties, 'AMQPLAIN', $this->uri->username, $this->uri->password));
    }

    private function connectionTune(ConnectionTune $tune): void
    {
        $heartbeat = (int) min($this->uri->heartbeat, $tune->heartbeat / 1000);
        $maxChannel = min($this->uri->channelMax, $tune->channelMax);
        $maxFrame = min($this->uri->frameMax, $tune->frameMax);

        $this->write(Protocol\Method::connectionTuneOk($maxChannel, $maxFrame, $heartbeat));
    }

    private function connectionOpen(Cancellation $cancellation): void
    {
        $this->write(Protocol\Method::connectionOpen($this->uri->vhost));
        $this->await(ConnectionOpenOk::class, cancellation: $cancellation);
    }

    /**
     * @param non-negative-int $channelId
     */
    private function openChannel(int $channelId, Cancellation $cancellation = new NullCancellation()): void
    {
        $this->write(Protocol\Method::channelOpen($channelId));
        $this->await(ChannelOpenOkFrame::class, $channelId, $cancellation);
    }

    /**
     * @return non-negative-int
     */
    private function allocateChannelId(): int
    {
        for ($id = $this->channelId; $id <= $this->uri->channelMax; ++$id) {
            if (!isset($this->channels[$id])) {
                $this->channelId = $id + 1;

                return $id;
            }
        }

        for ($id = 1; $id < $this->channelId; ++$id) {
            if (!isset($this->channels[$id])) {
                $this->channelId = $id + 1;

                return $id;
            }
        }

        throw Exception\NoAvailableChannel::forMaxChannel($this->uri->channelMax);
    }

    private function write(Frame $frame): void
    {
        $this->connection?->writeAt($frame->write($this->buffer));
    }

    /**
     * @template T of Protocol\Frame
     * @param non-negative-int $channelId
     * @param class-string<T> $frameType
     * @return T
     */
    private function await(
        string $frameType,
        int $channelId = 0,
        Cancellation $cancellation = new NullCancellation(),
    ): Frame {
        return $this->connection
            ?->subscribe($channelId, $frameType)
            ->await($cancellation);
    }
}
