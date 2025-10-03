<?php

/**
 * @package Remnant
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Remnant\FunctionIdentifier;

use DecodeLabs\Remnant\ClassIdentifier;
use DecodeLabs\Remnant\Frame;
use DecodeLabs\Remnant\FunctionIdentifier;
use DecodeLabs\Remnant\FunctionIdentifierTrait;
use DecodeLabs\Remnant\ViewOptions;
use ReflectionFunctionAbstract;

trait ClassTrait
{
    use FunctionIdentifierTrait;

    public readonly ClassIdentifier $class;
    public readonly string $name;

    public ?string $namespace {
        get {
            return $this->class->reflection?->getNamespaceName();
        }
    }

    public ?ReflectionFunctionAbstract $reflection {
        get {
            if (!$classRef = $this->class->reflection) {
                return null;
            }

            if (!$classRef->hasMethod($this->name)) {
                return null;
            }

            return $classRef->getMethod($this->name);
        }
    }

    public function __construct(
        string|ClassIdentifier $class,
        string $function
    ) {
        if (is_string($class)) {
            $class = Frame::createClassIdentifier($class);
        }

        $this->class = $class;
        $this->name = $function;
    }

    public function equals(
        FunctionIdentifier $identifier
    ): bool {
        return
            $identifier instanceof self &&
            $identifier->class->equals($this->class) &&
            $identifier->name === $this->name;
    }

    public function render(
        ?ViewOptions $options = null
    ): string {
        return $this->class . $this->separator . $this->name;
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
