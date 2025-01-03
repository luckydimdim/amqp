<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Delivery;

use Typhoon\Amqp091\Channel;
use Typhoon\Amqp091\Delivery;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 * @psalm-type Listener = callable(Delivery, Channel): void
 */
final class Consumer
{
    /** @var array<string, Listener> */
    private array $consumers = [];

    public function __construct(
        private readonly Receiver $receiver,
        private readonly Channel $channel,
    ) {}

    public function run(): void
    {
        $this->receiver->addListener(function (Delivery $delivery): void {
            if (!$delivery->returned) {
                $consumer = $this->consumers[$delivery->consumerTag] ?? null;
                if ($consumer !== null) {
                    $consumer($delivery, $this->channel);
                }
            }
        });
    }

    /**
     * @param Listener $consumer
     */
    public function register(string $consumerTag, callable $consumer): void
    {
        $this->consumers[$consumerTag] = $consumer;
    }

    /**
     * @param non-empty-string $consumerTag
     */
    public function unregister(string $consumerTag): void
    {
        unset($this->consumers[$consumerTag]);
    }
}
