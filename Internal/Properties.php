<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class Properties
{
    private const DEFAULT_PLATFORM = 'php';

    /** @var non-negative-int */
    private int $maxChannel = 0xFFFF;

    /** @var non-negative-int */
    private int $maxFrame = 0xFFFF;

    /** @var array<string, bool> */
    private array $capabilities = [
        'connection.blocked' => true,
        'basic.nack' => true,
        'publisher_confirms' => true,
    ];

    /** @var non-empty-string */
    private string $product = 'AMQP 0.9.1 Client';

    /** @var non-empty-string */
    private readonly string $version;

    /** @var non-empty-string */
    private readonly string $platform;

    public static function createDefault(): self
    {
        return new self();
    }

    /**
     * @param non-negative-int $maxChannel
     * @param non-negative-int $maxFrame
     */
    public function tune(
        int $maxChannel,
        int $maxFrame,
    ): void {
        $this->maxChannel = $maxChannel;
        $this->maxFrame = $maxFrame;
    }

    /**
     * @param non-empty-string $capability
     */
    public function capable(string $capability): bool
    {
        return $this->capabilities[$capability] ?? false;
    }

    /**
     * @return non-negative-int
     */
    public function maxChannel(): int
    {
        return $this->maxChannel;
    }

    /**
     * @return non-negative-int
     */
    public function maxFrame(): int
    {
        return $this->maxFrame;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'product' => $this->product,
            'version' => $this->version,
            'platform' => $this->platform,
            'capabilities' => $this->capabilities,
        ];
    }

    private function __construct()
    {
        $this->version = VersionProvider::provide();
        $this->platform = self::DEFAULT_PLATFORM;
    }
}
