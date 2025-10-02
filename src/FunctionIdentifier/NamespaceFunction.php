<?php

/**
 * @package Remnant
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Remnant\FunctionIdentifier;

use DecodeLabs\Remnant\FunctionIdentifier;
use DecodeLabs\Remnant\FunctionIdentifierTrait;
use DecodeLabs\Remnant\ViewOptions;
use ReflectionFunction;
use ReflectionFunctionAbstract;

class NamespaceFunction implements FunctionIdentifier
{
    use FunctionIdentifierTrait;

    public readonly string $namespace;
    public readonly string $name;

    public ?ReflectionFunctionAbstract $reflection {
        get => new ReflectionFunction($this->namespace . '\\' . $this->name);
    }

    public function __construct(
        string $namespace,
        string $function
    ) {
        $this->namespace = $namespace;
        $this->name = $function;
    }

    public function equals(
        FunctionIdentifier $identifier
    ): bool {
        return
            $identifier instanceof self &&
            $identifier->namespace === $this->namespace &&
            $identifier->name === $this->name;
    }

    public function render(
        ?ViewOptions $options = null
    ): string {
        return $this->namespace . '\\' . $this->name;
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
