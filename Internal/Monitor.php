<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal;

use Amp\DeferredFuture;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class Monitor
{
    /** @var array<int, DeferredFuture> */
    private array $defers = [];

    public function trace(DeferredFuture $deferred): void
    {
        $deferredId = spl_object_id($deferred);
        $this->defers[$deferredId] = $deferred;

        $deferred->getFuture()->finally(function () use ($deferredId): void {
            unset($this->defers[$deferredId]);
        });
    }

    public function cancel(\Throwable $e): void
    {
        foreach ($this->defers as $deferredId => $deferred) {
            $deferred->error($e);
            unset($this->defers[$deferredId]);
        }
    }
}
