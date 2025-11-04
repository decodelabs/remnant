<?php

/**
 * Remnant
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Remnant\Filter;

use DecodeLabs\Remnant\Filter;
use DecodeLabs\Remnant\Frame;

class Vendor implements Filter
{
    public function accepts(
        Frame $frame
    ): bool {
        return !str_contains($frame->callSite->file ?? '', '/vendor/');
    }
}
