<?php

declare(strict_types=1);

namespace Thesis\Amqp;

use Amp\Cancellation;
use Amp\CancelledException;
use Amp\NullCancellation;
use Amp\Socket;
use Thesis\Amqp\Internal\Hooks;
use Thesis\Amqp\Internal\Io\AmqpConnection;
use Thesis\Amqp\Internal\Properties;
use Thesis\Amqp\Internal\Protocol;
use Thesis\Amqp\Internal\Protocol\Auth;
use Thesis\Amqp\Internal\Protocol\Frame;

/**
 * @api
 */
final class Client
{
    private ?AmqpConnection $connection = null;

    /** @var non-negative-int */
    private int $channelId = 1;

    /** @var array<non-negative-int, Channel> */
    private array $channels = [];

    private readonly Properties $properties;

    private readonly Hooks $hooks;

    public function __construct(private readonly Config $config)
    {
        $this->properties = Properties::createDefault();
        $this->hooks = Hooks::create();
    }

    /**
     * @throws Socket\ConnectException
     * @throws CancelledException
     * @throws \Throwable
     */
    public function connect(): void
    {
        if ($this->connection !== null) {
            return;
        }

        $context = (new Socket\ConnectContext())
            ->withConnectTimeout($this->config->connectionTimeout);

        if ($this->config->tcpNoDelay) {
            $context = $context->withTcpNoDelay();
        }

        $socket = Socket\connect($this->config->connectionDsn(), $context);

        if ($this->config->scheme === Scheme::amqps) {
            $socket->setupTls();
        }

        $this->connection = new AmqpConnection($socket);

        $start = $this->connection->rpc(Frame\ProtocolHeader::frame, Frame\ConnectionStart::class);

        $tune = $this->connection->rpc(
            Protocol\Method::connectionStartOk($this->properties->toArray(), Auth\Mechanism::select(
                $this->config->sasl(),
                $start->mechanisms,
            )),
            Frame\ConnectionTune::class,
        );

        [$heartbeat, $channelMax, $frameMax] = [
            $this->config->heartbeat($tune->heartbeat),
            $this->config->channelMax($tune->channelMax),
            $this->config->frameMax($tune->frameMax),
        ];

        $this->connection->rpc(
            Protocol\Method::connectionTuneOk($channelMax, $frameMax, $heartbeat),
        );

        $this->properties->tune($channelMax, $frameMax);

        if ($heartbeat > 0) {
            $this->connection->heartbeat($heartbeat);
        }

        $this->connection->rpc(
            Protocol\Method::connectionOpen($this->config->vhost),
            Frame\ConnectionOpenOk::class,
        );

        $this->connection->ioLoop($this->hooks);

        $this->hooks->oneshot(0, Frame\ConnectionClose::class)->map(function (Frame\ConnectionClose $close): void {
            $this->connection()->writeFrame(Protocol\Method::connectionCloseOk());
            $this->connection()->close();

            $error = Exception\ConnectionWasClosed::byServer($close->replyCode, $close->replyText);

            foreach ($this->channels as $channel) {
                $channel->abandon($error);
            }

            $this->channels = [];
        });
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
        $this->connection()->close();
    }

    /**
     * @throws \Throwable
     */
    public function channel(Cancellation $cancellation = new NullCancellation()): Channel
    {
        $channelId = $this->allocateChannelId();
        $this->openChannel($channelId, $cancellation);

        return $this->channels[$channelId] = new Channel(
            $channelId,
            $this->connection(),
            $this->properties,
            $this->hooks,
        );
    }

    /**
     * @param non-negative-int $replyCode
     * @throws \Throwable
     */
    private function connectionClose(int $replyCode, string $replyText = '', Cancellation $cancellation = new NullCancellation()): void
    {
        $this->connection()->writeFrame(Protocol\Method::connectionClose($replyCode, $replyText));

        $this->await(Frame\ConnectionCloseOk::class, cancellation: $cancellation);
    }

    /**
     * @param non-negative-int $channelId
     * @throws \Throwable
     */
    private function openChannel(int $channelId, Cancellation $cancellation = new NullCancellation()): void
    {
        $this->connection()->writeFrame(Protocol\Method::channelOpen($channelId));

        $this->await(Frame\ChannelOpenOkFrame::class, $channelId, $cancellation);

        $this->hooks->anyOf(
            $channelId,
            [Frame\ChannelCloseOk::class, Frame\ChannelClose::class],
            function (Frame\ChannelCloseOk|Frame\ChannelClose $frame) use ($channelId): void {
                $channel = $this->channels[$channelId] ?? null;

                if ($channel !== null) {
                    unset($this->channels[$channelId]);

                    if ($frame instanceof Frame\ChannelClose) {
                        $this->connection()->writeFrame(Protocol\Method::channelCloseOk($channelId));
                        $channel->abandon(new Exception\ChannelWasClosed($frame->replyCode, $frame->replyText));
                    }

                    $this->hooks->unsubscribe($channelId);
                }
            },
        );
    }

    /**
     * @return non-negative-int
     */
    private function allocateChannelId(): int
    {
        for ($id = $this->channelId; $id <= $this->properties->maxChannel(); ++$id) {
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

        throw Exception\NoAvailableChannel::forMaxChannel($this->properties->maxChannel());
    }

    /**
     * @throws Exception\ConnectionIsClosed
     */
    private function connection(): AmqpConnection
    {
        return $this->connection ?: throw new Exception\ConnectionIsClosed();
    }

    /**
     * @template T of Frame
     * @param non-negative-int $channelId
     * @param class-string<T> $frameType
     * @return T
     */
    private function await(
        string $frameType,
        int $channelId = 0,
        Cancellation $cancellation = new NullCancellation(),
    ): Frame {
        return $this->hooks
            ->oneshot($channelId, $frameType)
            ->await($cancellation);
    }
}
