<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal;

use Typhoon\Amqp091\DeliveryMode;
use Typhoon\Amqp091\Message;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class MessageProperties
{
    private const FLAG_CONTENT_TYPE = 0x8000;
    private const FLAG_CONTENT_ENCODING = 0x4000;
    private const FLAG_HEADERS = 0x2000;
    private const FLAG_DELIVERY_MODE = 0x1000;
    private const FLAG_PRIORITY = 0x0800;
    private const FLAG_CORRELATION_ID = 0x0400;
    private const FLAG_REPLY_TO = 0x0200;
    private const FLAG_EXPIRATION = 0x0100;
    private const FLAG_MESSAGE_ID = 0x0080;
    private const FLAG_TIMESTAMP = 0x0040;
    private const FLAG_TYPE = 0x0020;
    private const FLAG_USER_ID = 0x0010;
    private const FLAG_APP_ID = 0x0008;

    /**
     * @param non-negative-int $bodyLen
     * @param ?int<0, 9> $priority
     */
    private function __construct(
        public readonly int $bodyLen = 0,
        public readonly array $headers = [],
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
    ) {}

    public static function fromMessage(Message $message): self
    {
        return new self(
            bodyLen: \strlen($message->body),
            headers: $message->headers,
            contentType: $message->contentType,
            contentEncoding: $message->contentEncoding,
            deliveryMode: $message->deliveryMode,
            priority: $message->priority,
            correlationId: $message->correlationId,
            replyTo: $message->replyTo,
            expiration: $message->expiration,
            messageId: $message->messageId,
            timestamp: $message->timestamp,
            type: $message->type,
            userId: $message->userId,
            appId: $message->appId,
        );
    }

    /**
     * @return non-negative-int
     */
    public function mask(): int
    {
        $mask = 0;

        if ($this->contentType !== null && $this->contentType !== '') {
            $mask |= self::FLAG_CONTENT_TYPE;
        }

        if ($this->contentEncoding !== null && $this->contentEncoding !== '') {
            $mask |= self::FLAG_CONTENT_ENCODING;
        }

        if (\count($this->headers) > 0) {
            $mask |= self::FLAG_HEADERS;
        }

        if ($this->deliveryMode !== DeliveryMode::Whatever) {
            $mask |= self::FLAG_DELIVERY_MODE;
        }

        if ($this->priority !== null) {
            $mask |= self::FLAG_PRIORITY;
        }

        if ($this->correlationId !== null && $this->correlationId !== '') {
            $mask |= self::FLAG_CORRELATION_ID;
        }

        if ($this->replyTo !== null && $this->replyTo !== '') {
            $mask |= self::FLAG_REPLY_TO;
        }

        if ($this->expiration !== null && $this->expiration !== '') {
            $mask |= self::FLAG_EXPIRATION;
        }

        if ($this->messageId !== null && $this->messageId !== '') {
            $mask |= self::FLAG_MESSAGE_ID;
        }

        if ($this->timestamp?->getTimestamp() > 0) {
            $mask |= self::FLAG_TIMESTAMP;
        }

        if ($this->type !== null && $this->type !== '') {
            $mask |= self::FLAG_TYPE;
        }

        if ($this->userId !== null && $this->userId !== '') {
            $mask |= self::FLAG_USER_ID;
        }

        if ($this->appId !== null && $this->appId !== '') {
            $mask |= self::FLAG_APP_ID;
        }

        /** @var non-negative-int */
        return $mask;
    }

    /**
     * @return non-negative-int
     */
    public function size(): int
    {
        $size = 0;

        if ($this->contentType !== null && $this->contentType !== '') {
            $size += 1 + \strlen($this->contentType);
        }

        if ($this->contentEncoding !== null && $this->contentEncoding !== '') {
            $size += 1 + \strlen($this->contentEncoding);
        }

        if (\count($this->headers) > 0) {
            $buffer = Io\Buffer::alloc();
            $buffer->writeTable($this->headers);

            $size += \count($buffer);
        }

        if ($this->deliveryMode !== DeliveryMode::Whatever) {
            ++$size;
        }

        if ($this->priority !== null) {
            ++$size;
        }

        if ($this->correlationId !== null && $this->correlationId !== '') {
            $size += 1 + \strlen($this->correlationId);
        }

        if ($this->replyTo !== null && $this->replyTo !== '') {
            $size += 1 + \strlen($this->replyTo);
        }

        if ($this->expiration !== null && $this->expiration !== '') {
            $size += 1 + \strlen($this->expiration);
        }

        if ($this->messageId !== null && $this->messageId !== '') {
            $size += 1 + \strlen($this->messageId);
        }

        if ($this->timestamp?->getTimestamp() > 0) {
            $size += 8;
        }

        if ($this->type !== null && $this->type !== '') {
            $size += 1 + \strlen($this->type);
        }

        if ($this->userId !== null && $this->userId !== '') {
            $size += 1 + \strlen($this->userId);
        }

        if ($this->appId !== null && $this->appId !== '') {
            $size += 1 + \strlen($this->appId);
        }

        /** @var non-negative-int */
        return $size;
    }

    /**
     * @param non-negative-int $mask
     */
    public function write(Io\WriteBytes $writer, int $mask): Io\WriteBytes
    {
        if ($this->hasSet($mask, self::FLAG_CONTENT_TYPE) && $this->contentType !== null) {
            $writer->writeString($this->contentType);
        }

        if ($this->hasSet($mask, self::FLAG_CONTENT_ENCODING) && $this->contentEncoding !== null) {
            $writer->writeString($this->contentEncoding);
        }

        if ($this->hasSet($mask, self::FLAG_HEADERS) && \count($this->headers) > 0) {
            $writer->writeTable($this->headers);
        }

        if ($this->hasSet($mask, self::FLAG_DELIVERY_MODE) && $this->deliveryMode !== DeliveryMode::Whatever) {
            $writer->writeUint8($this->deliveryMode->value);
        }

        if ($this->hasSet($mask, self::FLAG_PRIORITY) && $this->priority !== null) {
            $writer->writeUint8($this->priority);
        }

        if ($this->hasSet($mask, self::FLAG_CORRELATION_ID) && $this->correlationId !== null) {
            $writer->writeString($this->correlationId);
        }

        if ($this->hasSet($mask, self::FLAG_REPLY_TO) && $this->replyTo !== null) {
            $writer->writeString($this->replyTo);
        }

        if ($this->hasSet($mask, self::FLAG_EXPIRATION) && $this->expiration !== null) {
            $writer->writeString($this->expiration);
        }

        if ($this->hasSet($mask, self::FLAG_MESSAGE_ID) && $this->messageId !== null) {
            $writer->writeString($this->messageId);
        }

        if ($this->hasSet($mask, self::FLAG_TIMESTAMP) && $this->timestamp !== null && $this->timestamp->getTimestamp() > 0) {
            $writer->writeTimestamp($this->timestamp);
        }

        if ($this->hasSet($mask, self::FLAG_TYPE) && $this->type !== null) {
            $writer->writeString($this->type);
        }

        if ($this->hasSet($mask, self::FLAG_USER_ID) && $this->userId !== null) {
            $writer->writeString($this->userId);
        }

        if ($this->hasSet($mask, self::FLAG_APP_ID) && $this->appId !== null) {
            $writer->writeString($this->appId);
        }

        return $writer;
    }

    /**
     * @param non-negative-int $mask
     * @param self::* $property
     */
    private function hasSet(int $mask, int $property): bool
    {
        return ($mask & $property) > 0;
    }
}
