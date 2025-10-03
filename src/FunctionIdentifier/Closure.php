<?php

/**
 * @package Remnant
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Remnant\FunctionIdentifier;

use DecodeLabs\Remnant\FunctionIdentifier;
use DecodeLabs\Remnant\FunctionIdentifierTrait;
use DecodeLabs\Remnant\Location;
use DecodeLabs\Remnant\ViewOptions;
use ReflectionClass;
use ReflectionException;
use ReflectionFunctionAbstract;

class Closure implements FunctionIdentifier
{
    use FunctionIdentifierTrait;

    public readonly ?Location $location;

    public string $name {
        get => '{closure}';
    }

    public ?ReflectionFunctionAbstract $reflection {
        get => null;
    }

    public static function fromFunctionString(
        string $function
    ): self {
        return new self(
            self::functionStringToLocation($function)
        );
    }

    public static function functionStringToLocation(
        string $function
    ): ?Location {
        if (preg_match('/\{closure\:(?<file>[^:]+)\:(?<line>[0-9]+)(.+\:(?<finalLine>[0-9]+))?\}$/', $function, $matches)) {
            return new Location(
                file: $matches['file'],
                line: (int)($matches['finalLine'] ?? $matches['line'])
            );
        }

        if (preg_match('/\{closure\:((?<class>[a-zA-Z0-9_\\\]+)\:\:(?<method>[a-zA-Z0-9_]+)\(\)\:(?<line>[0-9]+)(.+\:(?<finalLine>[0-9]+))?\}$)/', $function, $matches)) {
            try {
                /** @var class-string $class */
                $class = $matches['class'];
                $ref = new ReflectionClass($class);
                $method = $ref->getMethod($matches['method']);
            } catch (ReflectionException $e) {
                return null;
            }

            if (false === ($file = $method->getFileName())) {
                return null;
            }

            return new Location(
                file: $file,
                line: (int)($matches['finalLine'] ?? $matches['line'])
            );
        }

        return null;
    }

    public function __construct(
        ?Location $location = null
    ) {
        $this->location = $location;
    }

    public function equals(
        FunctionIdentifier $identifier
    ): bool {
        return
            $identifier instanceof self &&
            (
                ($identifier->location?->equals($this->location) ?? false) ||
                (
                    $identifier->location === null &&
                    $this->location === null
                )
            );
    }

    public function isFunction(
        string ...$functions
    ): bool {
        return false;
    }

    public function render(
        ?ViewOptions $options = null
    ): string {
        $options ??= new ViewOptions();

        if ($this->location === null) {
            return '{closure}';
        }

        $location = $this->location->getPrettyFile($options);

        if ($this->location->line !== null) {
            $location .= ':' . $this->location->line;
        }

        return '{closure:' . $location . '}';
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
