<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal;

use Typhoon\Amqp091\Channel;
use Typhoon\Amqp091\Delivery;
use Typhoon\Amqp091\Internal\Protocol\Frame;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 * @psalm-type Listener = callable(Delivery): void
 */
final class Consumer
{
    private const WAIT = 0;
    private const HEADER = 1;
    private const BODY = 2;

    /** @var self::* */
    private int $step = self::WAIT;

    private ?Frame\BasicDeliver $delivery = null;

    private ?Frame\ContentHeader $header = null;

    /** @var non-negative-int */
    private int $messageSize = 0;

    private string $message = '';

    /** @var array<string, Listener> */
    private array $listeners = [];

    /** @var array<string, list<Delivery>> */
    private array $pending = [];

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
        $this->subscribe(Frame\ContentHeader::class, $this->onContentHeader(...));
        $this->subscribe(Frame\ContentBody::class, $this->onContentBody(...));
    }

    /**
     * @param Listener $callback
     */
    public function register(string $consumerTag, callable $callback): void
    {
        $this->listeners[$consumerTag] = $callback;

        foreach ($this->pending[$consumerTag] ?? [] as $i => $delivery) {
            $callback($delivery);
            unset($this->pending[$consumerTag][$i]);
        }
    }

    /**
     * @param non-empty-string $consumerTag
     */
    public function unregister(string $consumerTag): void
    {
        unset($this->listeners[$consumerTag]);
    }

    private function onBasicDeliver(Frame\BasicDeliver $delivery): void
    {
        if ($this->step === self::WAIT) {
            [$this->delivery, $this->step] = [$delivery, self::HEADER];
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

        \assert($this->delivery !== null, 'delivery must not be empty.');
        \assert($this->header !== null, 'header must not be empty.');

        $delivery = new Delivery(
            ack: $this->channel->ack(...),
            nack: $this->channel->nack(...),
            reject: $this->channel->reject(...),
            body: $this->message,
            headers: $this->header->properties->headers,
            deliveryTag: $this->delivery->deliveryTag,
            redelivered: $this->delivery->redelivered,
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
        );

        $listener = $this->listeners[$this->delivery->consumerTag] ?? function (Delivery $delivery): void {
            $this->pending[$this->delivery->consumerTag][] = $delivery;
        };

        $listener($delivery);

        $this->delivery = null;
        $this->header = null;
        $this->message = '';
        $this->step = self::WAIT;
    }

    /**
     * @template T of Protocol\Frame
     * @param class-string<T> $frameType
     * @param \Closure(T): void $callback
     */
    private function subscribe(string $frameType, \Closure $callback): void
    {
        // TODO split oneshot futures from subscriptions.

        $this->hooks
            ->subscribe($this->channelId, $frameType)
            ->map($callback)
            ->finally($this->run(...));
    }
}
