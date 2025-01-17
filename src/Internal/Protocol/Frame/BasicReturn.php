<?php

declare(strict_types=1);

namespace Thesis\Amqp\Internal\Protocol\Frame;

use Thesis\Amqp\Internal\Io;
use Thesis\Amqp\Internal\Protocol\Frame;

/**
 * @internal
 */
final class BasicReturn implements Frame
{
    /**
     * @param non-negative-int $replyCode
     */
    public function __construct(
        public readonly int $replyCode,
        public readonly string $replyText,
        public readonly string $exchange,
        public readonly string $routingKey,
    ) {}

    public static function read(Io\ReadBytes $reader): self
    {
        return new self(
            $reader->readUint16(),
            $reader->readString(),
            $reader->readString(),
            $reader->readString(),
        );
    }

    public function write(Io\WriteBytes $writer): void
    {
        $writer
            ->writeUint16($this->replyCode)
            ->writeString($this->replyText)
            ->writeString($this->exchange)
            ->writeString($this->routingKey);
    }
}
