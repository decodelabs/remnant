<?php

/**
 * @package Remnant
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Remnant;

use Closure;
use InvalidArgumentException;

class ViewOptions
{
    public ?Closure $redact = null;

    /**
     * @param array<Filter> $filters
     * @param Closure(string,mixed):bool $redact
     */
    public function __construct(
        public array $filters = [],
        public ArgumentFormat $argumentFormat = ArgumentFormat::Count,
        public int $maxStringLength = 16,
        Closure|true|null $redact = true
    ) {
        if (
            $maxStringLength <= 0 ||
            $maxStringLength > 100
        ) {
            throw new InvalidArgumentException('Max string length must be between 1 and 100');
        }

        if ($redact === true) {
            $this->redact = fn (string $key, mixed $value) => $key === 'password' || $key === 'secret' || $key === 'secretKey' || $key === 'token';
        } else {
            $this->redact = $redact;
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
