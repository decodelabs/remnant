<?php

/**
 * Remnant
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Remnant;

use ReflectionFunctionAbstract;
use Stringable;

interface FunctionIdentifier extends
    JsonSerializableWithOptions,
    Stringable
{
    public string $name { get; }
    public ?ReflectionFunctionAbstract $reflection { get; }

    public function isInternal(): bool;

    public function equals(
        FunctionIdentifier $identifier
    ): bool;

    public function isFunction(
        string ...$functions
    ): bool;

    public function render(
        ?ViewOptions $options = null
    ): string;
}
