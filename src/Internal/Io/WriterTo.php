<?php

declare(strict_types=1);

namespace Thesis\Amqp\Internal\Io;

use Thesis\ByteWriter\Writer;

/**
 * @internal
 */
interface WriterTo
{
    public function writeTo(Writer $writer): void;
}
