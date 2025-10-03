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

class GlobalFunction implements FunctionIdentifier
{
    use FunctionIdentifierTrait;

    public readonly string $name;

    public ?ReflectionFunctionAbstract $reflection {
        get => function_exists($this->name) ? new ReflectionFunction($this->name) : null;
    }

    public function __construct(
        string $function
    ) {
        $this->name = $function;
    }

    public function isInternal(): bool
    {
        if (
            $this->name === 'eval' ||
            $this->name === 'require' ||
            $this->name === 'require_once' ||
            $this->name === 'include' ||
            $this->name === 'include_once'
        ) {
            return true;
        }

        if ($reflection = $this->reflection) {
            return $reflection->isInternal();
        }

        return false;
    }

    public function equals(
        FunctionIdentifier $identifier
    ): bool {
        return
            $identifier instanceof self &&
            $identifier->name === $this->name;
    }

    public function render(
        ?ViewOptions $options = null
    ): string {
        $output = $this->name;

        if ($this->isInternal()) {
            $output = '[internal] ' . $output;
        }

        return $output;
    }

    public function jsonSerializeWithOptions(
        ?ViewOptions $options = null
    ): string {
        return $this->name;
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
