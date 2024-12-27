<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Connection;

use Amp\DeferredFuture;
use Amp\Future;
use Typhoon\Amqp091\Internal\Protocol;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 * @template-implements \IteratorAggregate<array-key, DeferredFuture<Protocol\Frame>>
 */
final class Hooks implements
    \IteratorAggregate,
    \Countable
{
    /** @var array<non-negative-int, array<class-string<Protocol\Frame>, list<DeferredFuture<Protocol\Frame>>>> */
    private array $defers = [];

    /** @var array<string, DeferredFuture<Protocol\Frame>> */
    private array $queue = [];

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
        $this->queue[spl_object_hash($deferred)] = $deferred;

        return $deferred->getFuture();
    }

    public function emit(Protocol\Request ...$requests): void
    {
        foreach ($requests as $request) {
            foreach ($this->defers[$request->channelId][$request->frame::class] ?? [] as $i => $f) {
                $f->complete($request->frame);
                unset($this->defers[$request->channelId][$request->frame::class][$i], $this->queue[spl_object_hash($f)]);
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
        yield from $this->queue;
    }

    public function count(): int
    {
        return \count($this->queue);
    }
}
