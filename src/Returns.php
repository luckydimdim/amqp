<?php

declare(strict_types=1);

namespace Thesis\Amqp;

use Amp\Pipeline;
use Thesis\Amqp\Internal\Delivery\DeliverySupervisor;

/**
 * @api
 * @template-implements \IteratorAggregate<array-key, Delivery>
 */
final class Returns implements \IteratorAggregate
{
    public static function create(DeliverySupervisor $supervisor): self
    {
        $returns = new self($supervisor);
        $returns->run();

        return $returns;
    }

    /**
     * @param \Closure(Delivery): void $map
     */
    public function map(\Closure $map): self
    {
        $this->callbacks[] = $map;

        return $this;
    }

    public function getIterator(): \Traversable
    {
        return ($this->iterator ??= $this->createIterator())->getIterator();
    }

    /** @var list<\Closure(Delivery): void> */
    private array $callbacks = [];

    /** @var ?Pipeline\ConcurrentIterator<Delivery> */
    private ?Pipeline\ConcurrentIterator $iterator = null;

    /** @var ?Pipeline\Queue<Delivery> */
    private ?Pipeline\Queue $queue = null;

    private function __construct(
        private readonly DeliverySupervisor $supervisor,
    ) {}

    private function run(): void
    {
        $this->supervisor->addReturnListener(function (Delivery $delivery): void {
            foreach ($this->callbacks as $callback) {
                $callback($delivery);
            }
        });

        $this->supervisor->addShutdownListener(function (): void {
            $this->queue?->complete();
        });
    }

    /**
     * @return Pipeline\ConcurrentIterator<Delivery>
     */
    private function createIterator(): Pipeline\ConcurrentIterator
    {
        /** @var Pipeline\Queue<Delivery> $queue */
        $queue = new Pipeline\Queue();
        $this->callbacks[] = $queue->push(...);
        $this->queue = $queue;

        return $queue->iterate();
    }
}
