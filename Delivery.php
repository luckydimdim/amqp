<?php

declare(strict_types=1);

namespace Typhoon\Amqp091;

/**
 * @api
 * @psalm-type Ack = callable(Delivery, bool): void
 * @psalm-type Nack = callable(Delivery, bool, bool): void
 * @psalm-type Reject = callable(Delivery, bool): void
 */
final class Delivery
{
    /** @var Ack */
    private $ack;

    /** @var Nack */
    private $nack;

    /** @var Reject */
    private $reject;

    /**
     * @param Ack $ack
     * @param Nack $nack
     * @param Reject $reject
     * @param array<string, mixed> $headers
     */
    public function __construct(
        callable $ack,
        callable $nack,
        callable $reject,
        public readonly string $body = '',
        public readonly array $headers = [],
        public readonly int $deliveryTag = 0,
        public readonly ?string $contentType = null,
        public readonly ?string $contentEncoding = null,
        public readonly DeliveryMode $deliveryMode = DeliveryMode::Whatever,
        public readonly ?int $priority = null,
        public readonly ?string $correlationId = null,
        public readonly ?string $replyTo = null,
        public readonly ?string $expiration = null,
        public readonly ?string $messageId = null,
        public readonly ?\DateTimeInterface $timestamp = null,
        public readonly ?string $type = null,
        public readonly ?string $userId = null,
        public readonly ?string $appId = null,
    ) {
        $this->ack = $ack;
        $this->nack = $nack;
        $this->reject = $reject;
    }

    public function ack(bool $multiple = false): void
    {
        ($this->ack)($this, $multiple);
    }

    public function nack(bool $multiple = false, bool $requeue = false): void
    {
        ($this->nack)($this, $multiple, $requeue);
    }

    public function reject(bool $requeue = false): void
    {
        ($this->reject)($this, $requeue);
    }
}
