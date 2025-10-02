<?php

/**
 * @package Remnant
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Remnant\ClassIdentifier;

use DecodeLabs\Remnant\ClassIdentifier;
use DecodeLabs\Remnant\ViewOptions;
use ReflectionClass;

class Native implements ClassIdentifier
{
    /**
     * @var class-string<object>
     */
    public readonly string $name;

    /**
     * @var ?ReflectionClass<object>
     */
    public ?ReflectionClass $reflection {
        get => new ReflectionClass($this->name);
    }

    /**
     * @param class-string<object> $class
     */
    public function __construct(
        string $class,
    ) {
        $this->name = $class;
    }

    public function equals(
        ClassIdentifier $identifier
    ): bool {
        return
            $identifier instanceof self &&
            $identifier->name === $this->name;
    }

    public function render(
        ?ViewOptions $options = null
    ): string {
        return $this->name;
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
