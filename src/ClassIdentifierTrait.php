<?php

/**
 * Remnant
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Remnant;

trait ClassIdentifierTrait
{
    use JsonSerializableWithOptionsTrait;

    public function jsonSerializeWithOptions(
        ?ViewOptions $options = null
    ): string {
        return $this->render($options);
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
