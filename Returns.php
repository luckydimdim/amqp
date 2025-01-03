<?php

declare(strict_types=1);

namespace Typhoon\Amqp091;

use Amp\Pipeline;
use Typhoon\Amqp091\Internal\Delivery\Receiver;

/**
 * @api
 * @template-implements \IteratorAggregate<array-key, Delivery>
 */
final class Returns implements \IteratorAggregate
{
    public static function fromReceiver(Receiver $receiver): self
    {
        $returns = new self($receiver);
        $returns->run();

        return $returns;
    }

    /** @var list<\Closure(Delivery): void> */
    private array $callbacks = [];

    /** @var ?Pipeline\ConcurrentIterator<Delivery> */
    private ?Pipeline\ConcurrentIterator $iterator = null;

    private function __construct(
        private readonly Receiver $receiver,
    ) {}

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

    private function run(): void
    {
        $this->receiver->addListener(function (Delivery $delivery): void {
            if ($delivery->returned) {
                foreach ($this->callbacks as $callback) {
                    $callback($delivery);
                }
            }
        });
    }

    /**
     * @return Pipeline\ConcurrentIterator<Delivery>
     */
    private function createIterator(): Pipeline\ConcurrentIterator
    {
        /** @var Pipeline\Queue<Delivery> $queue */
        $queue = new Pipeline\Queue();
        /** @psalm-suppress InvalidPropertyAssignmentValue https://github.com/vimeo/psalm/issues/4589 */
        $this->callbacks[] = $queue->push(...);

        return $queue->iterate();
    }
}
