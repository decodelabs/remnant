<?php

/**
 * @package Remnant
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Remnant;

use JsonSerializable;
use Stringable;

class Location implements
    JsonSerializable,
    Stringable
{
    use PathPrettifyTrait;

    public readonly string $file;

    public function __construct(
        string $file,
        public readonly int $line,
    ) {
        $this->file = str_replace('\\', '/', $file);
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
        return $this->getPrettyFile() . ':' . $this->line;
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'file' => $this->getPrettyFile(),
            'line' => $this->line,
        ];
    }
}
