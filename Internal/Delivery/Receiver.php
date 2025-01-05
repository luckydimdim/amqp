<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Delivery;

use Amp\Pipeline;
use Typhoon\Amqp091\Delivery;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class Receiver
{
    public static function create(DeliverySupervisor $supervisor): self
    {
        $receiver = new self($supervisor);
        $receiver->run();

        return $receiver;
    }

    public function receive(): ?Delivery
    {
        if (!$this->iterator->continue()) {
            return null;
        }

        return $this->iterator->getValue();
    }

    /** @var Pipeline\ConcurrentIterator<null|Delivery> */
    private Pipeline\ConcurrentIterator $iterator;

    /** @var Pipeline\Queue<null|Delivery> */
    private Pipeline\Queue $queue;

    private function __construct(
        private readonly DeliverySupervisor $supervisor,
    ) {
        /** @var Pipeline\Queue<null|Delivery> $queue */
        $queue = new Pipeline\Queue(bufferSize: 1);

        $this->queue = $queue;
        $this->iterator = $queue->iterate();
    }

    private function run(): void
    {
        /** @psalm-suppress InvalidArgument https://github.com/vimeo/psalm/issues/4589 */
        $this->supervisor->addGetListener($this->queue->push(...));
        $this->supervisor->addShutdownListener($this->queue->complete(...));
    }
}
