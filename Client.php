<?php

declare(strict_types=1);

namespace Typhoon\Amqp091;

use Amp\Cancellation;
use Amp\CancelledException;
use Amp\NullCancellation;
use Amp\Socket;
use Typhoon\Amqp091\Internal\Io\AmqpConnection;
use Typhoon\Amqp091\Internal\Protocol;
use Typhoon\Amqp091\Internal\Protocol\Frame;
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
    }

    /**
     * @throws Socket\ConnectException
     * @throws CancelledException
     * @throws \Throwable
     */
    public function connect(Cancellation $cancellation = new NullCancellation()): void
    {
        if ($this->connection === null) {
            $this->connection = new AmqpConnection(
                Socket\connect($this->uri->connectionDsn()),
            );

            $this->connection->writeFrame(Frame\ProtocolHeader::frame);

            $this->connectionStart(
                $this->await(Frame\ConnectionStart::class, cancellation: $cancellation),
            );

            $this->connectionTune(
                $this->await(Frame\ConnectionTune::class, cancellation: $cancellation),
            );

            $this->connectionOpen($cancellation);

            $this->connection->subscribe(0, Frame\ConnectionClose::class)->map(function (Frame\ConnectionClose $_): void {
                $this->connection?->writeFrame(Protocol\Method::connectionCloseOk());
                $this->connection?->close();

                // TODO Throw exception to the channel queue.

                $this->channels = [];
            });
        }
    }

    /**
     * @param non-negative-int $replyCode
     * @throws \Throwable
     */
    public function disconnect(int $replyCode = 200, string $replyText = '', Cancellation $cancellation = new NullCancellation()): void
    {
        foreach ($this->channels as $channel) {
            $channel->close($replyCode, $replyText);
        }

        $this->channels = [];

        $this->connectionClose($replyCode, $replyText, $cancellation);
        $this->connection?->close();
    }

    /**
     * @throws \Throwable
     */
    public function channel(Cancellation $cancellation = new NullCancellation()): Channel
    {
        $channelId = $this->allocateChannelId();
        $this->openChannel($channelId, $cancellation);

        return new Channel($channelId, $this->connection ?: throw new \LogicException('Connection is closed.'));
    }

    /**
     * @throws \Throwable
     */
    private function connectionStart(Frame\ConnectionStart $_): void
    {
        $this->connection?->writeFrame(
            Protocol\Method::connectionStartOk($this->properties, 'AMQPLAIN', $this->uri->username, $this->uri->password),
        );
    }

    /**
     * @throws \Throwable
     */
    private function connectionTune(Frame\ConnectionTune $tune): void
    {
        $heartbeat = (int) min($this->uri->heartbeat, $tune->heartbeat / 1000);
        \assert($heartbeat >= 0, 'heartbeat must not be negative.');

        $maxChannel = min($this->uri->channelMax, $tune->channelMax);
        \assert($maxChannel >= 0, 'max channel must not be negative.');

        $maxFrame = min($this->uri->frameMax, $tune->frameMax);
        \assert($maxFrame >= 0, 'max frame must not be negative.');

        $this->connection?->writeFrame(
            Protocol\Method::connectionTuneOk($maxChannel, $maxFrame, $heartbeat),
        );
    }

    /**
     * @throws \Throwable
     */
    private function connectionOpen(Cancellation $cancellation): void
    {
        $this->connection?->writeFrame(Protocol\Method::connectionOpen($this->uri->vhost));

        $this->await(Frame\ConnectionOpenOk::class, cancellation: $cancellation);
    }

    /**
     * @param non-negative-int $replyCode
     * @throws \Throwable
     */
    private function connectionClose(int $replyCode, string $replyText = '', Cancellation $cancellation = new NullCancellation()): void
    {
        $this->connection?->writeFrame(Protocol\Method::connectionClose($replyCode, $replyText));

        $this->await(Frame\ConnectionCloseOk::class, cancellation: $cancellation);
    }

    /**
     * @param non-negative-int $channelId
     * @throws \Throwable
     */
    private function openChannel(int $channelId, Cancellation $cancellation = new NullCancellation()): void
    {
        $this->connection?->writeFrame(Protocol\Method::channelOpen($channelId));

        $this->await(Frame\ChannelOpenOkFrame::class, $channelId, $cancellation);

        $this->connection
            ?->subscribeAny($channelId, Frame\ChannelCloseOk::class, Frame\ChannelClose::class)
            ->map(function (Frame\ChannelCloseOk|Frame\ChannelClose $frame) use ($channelId): void {
                $this->connection?->unsubscribe($channelId);
                unset($this->channels[$channelId]);

                if ($frame instanceof Frame\ChannelClose) {
                    $this->connection?->writeFrame(Protocol\Method::channelCloseOk($channelId));
                }
            });
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
