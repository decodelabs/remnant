<?php

/**
 * @package Remnant
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Remnant\Anchor;

use DecodeLabs\Remnant\Anchor;
use DecodeLabs\Remnant\Frame;

class Rewind implements Anchor
{
    public function __construct(
        public int $offset
    ) {
    }

    public function accepts(
        int $offset,
        Frame $frame
    ): bool {
        return $offset >= $this->offset;
    }
}
