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
    public static function create(DeliverySupervisor $supervisor, Channel $channel): self
    {
        $consumer = new self($supervisor, $channel);
        $consumer->run();

        return $consumer;
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

    /** @var array<string, Listener> */
    private array $consumers = [];

    private function __construct(
        private readonly DeliverySupervisor $supervisor,
        private readonly Channel $channel,
    ) {}

    private function run(): void
    {
        $this->supervisor->addConsumeListener(function (Delivery $delivery): void {
            $consumer = $this->consumers[$delivery->consumerTag] ?? null;
            if ($consumer !== null) {
                $consumer($delivery, $this->channel);
            }
        });
    }
}
