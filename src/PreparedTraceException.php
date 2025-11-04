<?php

/**
 * Remnant
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Remnant;

use Throwable;

interface PreparedTraceException extends Throwable
{
    public Trace $stackTrace { get; }
    public ?Frame $stackFrame { get; }
}
