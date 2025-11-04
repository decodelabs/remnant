<?php

/**
 * Remnant
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Remnant;

trait FunctionIdentifierTrait
{
    use JsonSerializableWithOptionsTrait;

    public function isInternal(): bool
    {
        return false;
    }

    public function isFunction(
        string ...$functions
    ): bool {
        return in_array($this->name, $functions, true);
    }

    public function jsonSerializeWithOptions(
        ?ViewOptions $options = null
    ): string {
        return $this->render($options);
    }
}
