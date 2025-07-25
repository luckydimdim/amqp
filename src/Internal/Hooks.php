<?php

declare(strict_types=1);

namespace Thesis\Amqp\Internal;

use Amp\DeferredFuture;
use Amp\Future;

/**
 * @internal
 */
final class Hooks implements \Countable
{
    /**
     * @var array<non-negative-int, array<non-empty-string, array<non-negative-int, callable(Protocol\Frame): void>>>
     */
    private array $defers = [];

    /**
     * @var array<non-negative-int, array<int, DeferredFuture<Protocol\Frame>>>
     */
    private array $queue = [];

    /**
     * @template T of Protocol\Frame
     * @param non-negative-int $channelId
     * @param non-empty-list<class-string<T>>|class-string<T> $frameTypes
     * @param callable(T): void $subscriber
     */
    public function anyOf(int $channelId, array|string $frameTypes, callable $subscriber): void
    {
        if (!\is_array($frameTypes)) {
            $frameTypes = [$frameTypes];
        }

        foreach ($frameTypes as $frameType) {
            $idx = \count($this->defers[$channelId][$frameType] ?? []);

            $this->defers[$channelId][$frameType][]
                = function (Protocol\Frame $frame) use ($channelId, $frameType, $idx, $subscriber): void {
                    /** @var T $frame */
                    $subscriber($frame);
                    unset($this->defers[$channelId][$frameType][$idx]);
                };
        }
    }

    /**
     * @template T of Protocol\Frame
     * @param non-negative-int $channelId
     * @param class-string<T> $frameType
     * @return Future<T>
     */
    public function oneshot(int $channelId, string $frameType): Future
    {
        /** @var DeferredFuture<T> $deferred */
        $deferred = new DeferredFuture();

        $idx = \count($this->defers[$channelId][$frameType] ?? []);
        $this->defers[$channelId][$frameType][]
            = function (Protocol\Frame $frame) use ($deferred, $channelId, $frameType, $idx): void {
                if (!$deferred->isComplete()) {
                    $deferred->complete($frame);
                }
                unset(
                    $this->defers[$channelId][$frameType][$idx],
                    $this->queue[$channelId][spl_object_id($deferred)],
                );
            };
        $this->queue[$channelId][spl_object_id($deferred)] = $deferred;

        return $deferred->getFuture();
    }

    /**
     * @template T of Protocol\Frame
     * @param non-negative-int $channelId
     * @param class-string<T> $frameType
     * @param callable(T): void $subscriber
     */
    public function subscribe(int $channelId, string $frameType, callable $subscriber): void
    {
        /** @phpstan-ignore assign.propertyType */
        $this->defers[$channelId][$frameType][] = $subscriber;
    }

    public function reject(int $channelId, \Throwable $e): void
    {
        $defers = $this->queue[$channelId] ?? [];
        unset($this->queue[$channelId], $this->defers[$channelId]);

        foreach ($defers as $f) {
            $f->error($e);
        }
    }

    /**
     * @param non-negative-int $channelId
     */
    public function unsubscribe(int $channelId): void
    {
        unset($this->defers[$channelId], $this->queue[$channelId]);
    }

    public function emit(Protocol\Request $request): void
    {
        foreach ($this->defers[$request->channelId][$request->frame::class] ?? [] as $f) {
            $f($request->frame);
        }
    }

    public function error(\Throwable $e): void
    {
        try {
            foreach ($this->queue as $deferred) {
                foreach ($deferred as $future) {
                    $future->error($e);
                }
            }
        } finally {
            $this->complete();
        }
    }

    public function complete(): void
    {
        $this->defers = [];
        $this->queue = [];
    }

    public function count(): int
    {
        return \count($this->queue);
    }
}
