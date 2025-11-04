<?php

/**
 * Remnant
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Remnant;

/**
 * @phpstan-require-implements JsonSerializableWithOptions
 */
trait JsonSerializableWithOptionsTrait
{
    public function jsonSerialize(): mixed
    {
        return $this->jsonSerializeWithOptions();
    }
}
