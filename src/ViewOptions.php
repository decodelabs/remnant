<?php

/**
 * @package Remnant
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Remnant;

class ViewOptions
{
    /**
     * @param array<Filter> $filters
     */
    public function __construct(
        public array $filters = [],
        public ArgumentFormat $argumentFormat = ArgumentFormat::Count,
    ) {
    }

    public function filter(
        Frame $frame
    ): bool {
        foreach ($this->filters as $filter) {
            if (!$filter->accepts($frame)) {
                return false;
            }
        }

        return true;
    }
}
