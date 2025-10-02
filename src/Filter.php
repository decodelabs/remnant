<?php

/**
 * @package Remnant
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Remnant;

interface Filter
{
    public function accepts(
        Frame $frame
    ): bool;
}
