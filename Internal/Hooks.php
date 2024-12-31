<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal;

use Amp\DeferredFuture;
use Amp\Future;
use Revolt\EventLoop;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 * @template-implements \IteratorAggregate<non-negative-int, DeferredFuture<Protocol\Frame>>
 */
final class Hooks implements
    \IteratorAggregate,
    \Countable
{
    /** @var array<non-negative-int, array<class-string<Protocol\Frame>, list<DeferredFuture<Protocol\Frame>>>> */
    private array $defers = [];

    /** @var array<non-negative-int, array<int, DeferredFuture<Protocol\Frame>>> */
    private array $queue = [];

    /** @var array<non-negative-int, array<class-string<Protocol\Frame>, list<Protocol\Request>>> */
    private array $pending = [];

    /**
     * @template T of Protocol\Frame
     * @param non-negative-int $channelId
     * @param class-string<T> ...$frameTypes
     * @return Future<T>
     */
    public function anyOf(int $channelId, string ...$frameTypes): Future
    {
        $futures = [];

        foreach ($frameTypes as $frameType) {
            $futures[] = $this->subscribe($channelId, $frameType);
        }

        /** @var DeferredFuture<T> $deferred */
        $deferred = new DeferredFuture();
        EventLoop::queue(static function () use ($deferred, $futures): void {
            $deferred->complete(Future\awaitFirst($futures));
        });

        return $deferred->getFuture();
    }

    /**
     * @template T of Protocol\Frame
     * @param non-negative-int $channelId
     * @param class-string<T> $frameType
     * @return Future<T>
     */
    public function subscribe(int $channelId, string $frameType): Future
    {
        /** @var DeferredFuture<T> $deferred */
        $deferred = new DeferredFuture();
        $this->defers[$channelId][$frameType][] = $deferred;
        $this->queue[$channelId][spl_object_id($deferred)] = $deferred;

        $this->emit(...$this->pending[$channelId][$frameType] ?? []);

        return $deferred->getFuture();
    }

    /**
     * @param non-negative-int $channelId
     */
    public function unsubscribe(int $channelId): void
    {
        unset($this->defers[$channelId], $this->queue[$channelId]);
    }

    public function emit(Protocol\Request ...$requests): void
    {
        foreach ($requests as $request) {
            $defers = $this->defers[$request->channelId][$request->frame::class] ?? [];
            if ($defers === []) {
                $this->pending[$request->channelId][$request->frame::class][] = $request;
            } else {
                foreach ($defers as $i => $f) {
                    $f->complete($request->frame);
                    unset(
                        $this->defers[$request->channelId][$request->frame::class][$i],
                        $this->queue[$request->channelId][spl_object_id($f)],
                        $this->pending[$request->channelId][$request->frame::class],
                    );
                }
            }
        }
    }

    public function error(\Throwable $e): void
    {
        foreach ($this as $f) {
            $f->error($e);
        }

        $this->complete();
    }

    public function complete(): void
    {
        [$this->defers, $this->queue] = [[], []];
    }

    public function getIterator(): \Traversable
    {
        foreach ($this->queue as $channelId => $deferred) {
            if (($futureId = array_key_first($deferred)) !== null) {
                if (($future = $deferred[$futureId] ?? null) !== null) {
                    yield $channelId => $future;
                }
            }
        }
    }

    public function count(): int
    {
        return \count($this->queue);
    }
}
