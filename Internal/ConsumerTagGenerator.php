<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
final class ConsumerTagGenerator
{
    private const TAG_LENGTH_MAX = 0xFF;

    private const PACKAGE_NAME = 'typhoon/amqp091';

    /** @var non-empty-string */
    private readonly string $commandName;

    /** @var non-negative-int */
    private int $consumerId = 0;

    public function __construct()
    {
        $this->commandName = ($_SERVER['argv'][0] ?? self::PACKAGE_NAME) ?: self::PACKAGE_NAME;
    }

    /**
     * @return non-empty-string
     */
    public function next(): string
    {
        $prefix = 'ctag-';
        $infix = $this->commandName;
        $suffix = \sprintf('-%d', ++$this->consumerId);

        if (\strlen($prefix) + \strlen($infix) + \strlen($suffix) > self::TAG_LENGTH_MAX) {
            $infix = self::PACKAGE_NAME;
        }

        return "{$prefix}{$infix}{$suffix}";
    }
}
