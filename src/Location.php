<?php

/**
 * @package Remnant
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Remnant;

use Stringable;

class Location implements Stringable
{
    use PathPrettifyTrait;

    public function __construct(
        public string $file,
        public int $line,
    ) {
    }

    public function equals(
        ?Location $location
    ): bool {
        if ($location === null) {
            return false;
        }

        return
            $location->file === $this->file &&
            $location->line === $this->line;
    }

    public function getPrettyFile(): string
    {
        return self::prettifyPath($this->file);
    }

    public function __toString(): string
    {
        return $this->getPrettyFile() . ' : ' . $this->line;
    }
}
