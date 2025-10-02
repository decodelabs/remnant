<?php

/**
 * @package Remnant
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Remnant;

use InvalidArgumentException;

class ViewOptions
{
    /**
     * @param array<Filter> $filters
     */
    public function __construct(
        public array $filters = [],
        public ArgumentFormat $argumentFormat = ArgumentFormat::Count,
        public int $maxStringLength = 16,
        /** @var list<string> */
        public array $redactKeys = [],
    ) {
        if (
            $maxStringLength <= 0 ||
            $maxStringLength > 100
        ) {
            throw new InvalidArgumentException('Max string length must be between 1 and 100');
        }
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
