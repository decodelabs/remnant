<?php

/**
 * @package Remnant
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Remnant;

use ReflectionClass;
use Stringable;

interface ClassIdentifier extends
    JsonSerializableWithOptions,
    Stringable
{
    public string $name { get; }

    /**
     * @var ?ReflectionClass<object>
     */
    public ?ReflectionClass $reflection { get; }

    public function equals(
        ClassIdentifier $identifier
    ): bool;

    public function render(
        ?ViewOptions $options = null
    ): string;
}
