<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Io;

use Typhoon\ByteWriter\Writer;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
interface WriterTo
{
    public function writeTo(Writer $writer): void;
}
