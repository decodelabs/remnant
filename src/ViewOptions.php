<?php

/**
 * @package Remnant
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Remnant;

use Closure;
use DecodeLabs\Monarch;
use InvalidArgumentException;

class ViewOptions
{
    public ?Closure $redact = null;

    /**
     * @param array<Filter> $filters
     * @param Closure(string,mixed):bool $redact
     */
    public function __construct(
        public ?string $rootPath = null,
        public array $filters = [],
        public ArgumentFormat $argumentFormat = ArgumentFormat::Count,
        public int $maxStringLength = 16,
        public int $gutter = 4,
        Closure|true|null $redact = true,
        public bool $collapseSingleLineArguments = false,
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

        if (
            $rootPath === null &&
            class_exists(Monarch::class)
        ) {
            $this->rootPath = Monarch::getPaths()->root;
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
