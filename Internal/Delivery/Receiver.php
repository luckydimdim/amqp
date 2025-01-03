<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Delivery;

use Typhoon\Amqp091\Channel;
use Typhoon\Amqp091\Delivery;
use Typhoon\Amqp091\Internal\Hooks;
use Typhoon\Amqp091\Internal\Protocol\Frame;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 * @psalm-type Listener = callable(Delivery): void
 */
final class Receiver
{
    private const WAIT = 0;
    private const HEADER = 1;
    private const BODY = 2;

    /** @var self::* */
    private int $step = self::WAIT;

    private ?Frame\BasicDeliver $delivery = null;

    private ?Frame\BasicReturn $return = null;

    private ?Frame\ContentHeader $header = null;

    /** @var non-negative-int */
    private int $messageSize = 0;

    private string $message = '';

    /** @var list<Listener> */
    private array $listeners = [];

    /**
     * @param non-negative-int $channelId
     */
    public function __construct(
        private readonly Channel $channel,
        private readonly Hooks $hooks,
        private readonly int $channelId,
    ) {}

    public function run(): void
    {
        $this->subscribe(Frame\BasicDeliver::class, $this->onBasicDeliver(...));
        $this->subscribe(Frame\BasicReturn::class, $this->onBasicReturn(...));
        $this->subscribe(Frame\ContentHeader::class, $this->onContentHeader(...));
        $this->subscribe(Frame\ContentBody::class, $this->onContentBody(...));
    }

    /**
     * @param Listener $callback
     */
    public function addListener(callable $callback): void
    {
        $this->listeners[] = $callback;
    }

    public function stop(): void
    {
        $this->listeners = [];
    }

    private function onBasicDeliver(Frame\BasicDeliver $delivery): void
    {
        if ($this->step === self::WAIT) {
            [$this->delivery, $this->step] = [$delivery, self::HEADER];
        }
    }

    private function onBasicReturn(Frame\BasicReturn $return): void
    {
        if ($this->step === self::WAIT) {
            [$this->return, $this->step] = [$return, self::HEADER];
        }
    }

    private function onContentHeader(Frame\ContentHeader $header): void
    {
        if ($this->step === self::HEADER) {
            $this->header = $header;
            $this->step = self::BODY;
            $this->messageSize = $this->header->bodySize;

            $this->runListeners();
        }
    }

    private function onContentBody(Frame\ContentBody $body): void
    {
        if ($this->step === self::BODY) {
            $this->message .= $body->body;
            $this->messageSize = max($this->messageSize - \strlen($body->body), 0);

            $this->runListeners();
        }
    }

    private function runListeners(): void
    {
        if ($this->messageSize !== 0) {
            return;
        }

        \assert($this->delivery !== null || $this->return !== null, 'delivery or return must not be empty.');
        \assert($this->header !== null, 'header must not be empty.');

        $delivery = new Delivery(
            ack: $this->channel->ack(...),
            nack: $this->channel->nack(...),
            reject: $this->channel->reject(...),
            body: $this->message,
            exchange: $this->delivery?->exchange ?? $this->return?->exchange ?? '',
            routingKey: $this->delivery?->routingKey ?? $this->return?->routingKey ?? '',
            headers: $this->header->properties->headers,
            deliveryTag: $this->delivery?->deliveryTag ?? 0,
            consumerTag: $this->delivery?->consumerTag ?? '',
            redelivered: $this->delivery?->redelivered ?? false,
            contentType: $this->header->properties->contentType,
            contentEncoding: $this->header->properties->contentEncoding,
            deliveryMode: $this->header->properties->deliveryMode,
            priority: $this->header->properties->priority,
            correlationId: $this->header->properties->correlationId,
            replyTo: $this->header->properties->replyTo,
            expiration: $this->header->properties->expiration,
            messageId: $this->header->properties->messageId,
            timestamp: $this->header->properties->timestamp,
            type: $this->header->properties->type,
            userId: $this->header->properties->userId,
            appId: $this->header->properties->appId,
            returned: $this->return !== null,
        );

        foreach ($this->listeners as $listener) {
            $listener($delivery);
        }

        $this->delivery = null;
        $this->return = null;
        $this->header = null;
        $this->message = '';
        $this->step = self::WAIT;
    }

    /**
     * @template T of Frame
     * @param class-string<T> $frameType
     * @param \Closure(T): void $callback
     */
    private function subscribe(string $frameType, \Closure $callback): void
    {
        $this->hooks->subscribe($this->channelId, $frameType, $callback);
    }
}
