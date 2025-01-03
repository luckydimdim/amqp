<?php

declare(strict_types=1);

namespace Typhoon\Amqp091;

use Amp\Cancellation;
use Amp\Future;

/**
 * @api
 */
final class Confirmation
{
    /**
     * @param iterable<self> $confirmations
     * @return \Traversable<non-negative-int, PublishResult>
     */
    public static function awaitAll(iterable $confirmations, ?Cancellation $cancellation = null): \Traversable
    {
        foreach (self::iterate($confirmations, $cancellation) as $deliveryTag => $future) {
            yield $deliveryTag => $future->await($cancellation);
        }
    }

    /**
     * @param iterable<self> $confirmations
     * @return iterable<non-negative-int, Future<PublishResult>>
     */
    public static function iterate(iterable $confirmations, ?Cancellation $cancellation = null): iterable
    {
        $futures = [];
        foreach ($confirmations as $confirmation) {
            $futures[$confirmation->deliveryTag] = $confirmation->future;
        }

        return Future::iterate($futures, $cancellation);
    }

    private PublishResult $result;

    /**
     * @param non-negative-int $deliveryTag
     * @param Future<PublishResult> $future
     * @param \Closure(): void $cancel
     */
    public function __construct(
        public readonly int $deliveryTag,
        private readonly Future $future,
        private readonly \Closure $cancel,
    ) {
        $this->result = PublishResult::Waiting;
        $this->future->map(function (PublishResult $result): void {
            $this->result = $result;
        });
    }

    public function await(?Cancellation $cancellation = null): PublishResult
    {
        $cancellation?->subscribe($this->cancel(...));

        return $this->future->await($cancellation);
    }

    /**
     * @return Future<PublishResult>
     */
    public function future(): Future
    {
        return $this->future;
    }

    public function result(): PublishResult
    {
        return $this->result;
    }

    public function cancel(): void
    {
        ($this->cancel)();
    }
}
