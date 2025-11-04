<?php

/**
 * Remnant
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Remnant;

interface Anchor
{
    public function accepts(
        int $offset,
        Frame $frame
    ): bool;
}
