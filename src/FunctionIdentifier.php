<?php

/**
 * @package Remnant
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Remnant;

use JsonSerializable;
use ReflectionFunctionAbstract;
use Stringable;

interface FunctionIdentifier extends
    JsonSerializable,
    Stringable
{
    public string $name { get; }
    public ?ReflectionFunctionAbstract $reflection { get; }

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
