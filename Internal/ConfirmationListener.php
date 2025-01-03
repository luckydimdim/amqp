<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal;

use Amp\DeferredFuture;
use Typhoon\Amqp091\Confirmation;
use Typhoon\Amqp091\Internal\Protocol\Frame\BasicAck;
use Typhoon\Amqp091\Internal\Protocol\Frame\BasicNack;
use Typhoon\Amqp091\PublishedResult;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class ConfirmationListener implements \Countable
{
    /** @var non-negative-int */
    private int $deliveryTag = 0;

    /** @var non-negative-int */
    private int $confirmed = 0;

    /** @var array<non-negative-int, DeferredFuture<PublishedResult>> */
    private array $confirms = [];

    /**
     * @param non-negative-int $channelId
     */
    public function __construct(
        private readonly Hooks $hooks,
        private readonly int $channelId,
    ) {}

    public function listen(): void
    {
        [$this->deliveryTag, $this->confirmed, $this->confirms] = [0, 0, []];

        $this->hooks->subscribe($this->channelId, BasicAck::class, $this->confirm(PublishedResult::Acked));
        $this->hooks->subscribe($this->channelId, BasicNack::class, $this->confirm(PublishedResult::Nacked));
    }

    public function newConfirmation(): Confirmation
    {
        $deliveryTag = ++$this->deliveryTag;

        /** @var DeferredFuture<PublishedResult> $deferred */
        $deferred = new DeferredFuture();
        $this->confirms[$deliveryTag] = $deferred;

        return new Confirmation($deliveryTag, $deferred->getFuture(), function () use ($deliveryTag, $deferred): void {
            unset($this->confirms[$deliveryTag]);
            $deferred->complete(PublishedResult::Canceled);
        });
    }

    public function count(): int
    {
        return \count($this->confirms);
    }

    /**
     * @return callable(BasicAck|BasicNack): void
     */
    private function confirm(PublishedResult $result): callable
    {
        return function (BasicAck|BasicNack $frame) use ($result): void {
            if ($frame->multiple) {
                for ($i = $this->confirmed + 1; $i < $frame->deliveryTag; ++$i) {
                    $this->complete($i, $result);
                }
            }

            $this->complete($frame->deliveryTag, $result);
        };
    }

    /**
     * @param non-negative-int $deliveryTag
     */
    private function complete(int $deliveryTag, PublishedResult $result): void
    {
        $confirmation = $this->confirms[$deliveryTag] ?? null;
        if ($confirmation !== null) {
            $confirmation->complete($result);
            unset($this->confirms[$deliveryTag]);

            $this->confirmed = $deliveryTag;
        }
    }
}
