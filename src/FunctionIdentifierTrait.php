<?php

/**
 * @package Remnant
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Remnant;

trait FunctionIdentifierTrait
{
    public function isFunction(
        string ...$functions
    ): bool {
        return in_array($this->name, $functions, true);
    }
}
