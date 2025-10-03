<?php

/**
 * @package Remnant
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Remnant;

use DecodeLabs\Remnant\ClassIdentifier\Anonymous as AnonymousClass;
use DecodeLabs\Remnant\ClassIdentifier\Native as NativeClass;
use DecodeLabs\Remnant\FunctionIdentifier\Closure as ClosureFunction;
use DecodeLabs\Remnant\FunctionIdentifier\GlobalFunction;
use DecodeLabs\Remnant\FunctionIdentifier\NamespaceFunction;
use DecodeLabs\Remnant\FunctionIdentifier\ObjectMethod;
use DecodeLabs\Remnant\FunctionIdentifier\StaticMethod;
use OutOfBoundsException;
use Stringable;
use UnexpectedValueException;

use function array_shift;
use function count;
use function explode;
use function implode;
use function str_contains;
use function str_starts_with;

class Frame implements
    JsonSerializableWithOptions,
    Stringable
{
    use JsonSerializableWithOptionsTrait;

    public readonly FunctionIdentifier $function;
    public readonly ArgumentList $arguments;
    public readonly ?Location $callSite;
    public readonly ?Location $location;

    /**
     * Generate a new trace and pull out a single frame
     * depending on the rewind range
     */
    public static function create(
        int $rewind = 0
    ): Frame {
        $data = debug_backtrace();

        if ($rewind >= count($data) - 1) {
            throw new OutOfBoundsException('Stack rewind out of stack frame range');
        }

        if ($rewind) {
            $data = array_slice($data, $rewind);
        }

        $last = array_shift($data);
        $output = array_shift($data);

        $output['callFile'] = $output['file'] ?? null;
        $output['callLine'] = $output['line'] ?? null;
        $output['file'] = $last['file'] ?? null;
        $output['line'] = $last['line'] ?? null;

        return self::fromDebugBacktrace($output);
    }


    /**
     * @param array<string,mixed> $frame
     */
    public static function fromDebugBacktrace(
        array $frame
    ): self {
        $namespace = $class = null;
        $arguments = [];

        // Function
        if (
            isset($frame['function']) &&
            is_string($frame['function'])
        ) {
            $function = $frame['function'];
        } else {
            $function = '{closure}';
        }

        // Class
        if (
            isset($frame['class']) &&
            is_string($frame['class'])
        ) {
            $object = $frame['object'] ?? null;

            if (!is_object($object)) {
                $object = null;
            }

            $class = self::createClassIdentifier(
                class: $frame['class'],
                object: $object
            );
        }

        // Namespace
        if (str_contains('\\', $function)) {
            $parts = explode('\\', $function);
            $function = array_pop($parts);

            if (!empty($parts)) {
                $namespace = implode('\\', $parts);
            }
        }



        // Type
        $type = null;

        if (isset($frame['type'])) {
            switch ($frame['type']) {
                case '::':
                    $type = StaticMethod::class;
                    break;

                case '->':
                    $type = ObjectMethod::class;
                    break;
            }
        } elseif ($namespace !== null) {
            $type = NamespaceFunction::class;
        } else {
            $type = GlobalFunction::class;
        }

        if (str_starts_with($function, '{closure')) {
            $type = ClosureFunction::class;
        }

        // Args
        if (isset($frame['args'])) {
            $arguments = (array)$frame['args'];
        }

        if (
            $function === '__callStatic' ||
            $function === '__call'
        ) {
            /** @var string $func */
            $func = array_shift($arguments);
            $function = $func;
        }

        $function = match ($type) {
            StaticMethod::class => new StaticMethod(
                $class ?? throw new UnexpectedValueException('Class is required for static method'),
                $function
            ),
            ObjectMethod::class => new ObjectMethod(
                $class ?? throw new UnexpectedValueException('Class is required for object method'),
                $function
            ),
            NamespaceFunction::class => new NamespaceFunction(
                $namespace ?? throw new UnexpectedValueException('Namespace is required for namespace function'),
                $function
            ),
            GlobalFunction::class => new GlobalFunction($function),
            ClosureFunction::class => ClosureFunction::fromFunctionString($function),
            default => throw new UnexpectedValueException('Invalid function type: ' . $type),
        };

        return new self(
            function: $function,
            arguments: new ArgumentList($arguments, $function),
            callSite: self::extractLocation($frame, 'call'),
            location: self::extractLocation($frame),
        );
    }

    /**
     * @param array<string,mixed> $frame
     */
    private static function extractLocation(
        array $frame,
        ?string $prefix = null
    ): ?Location {
        $file = $line = null;
        $fileKey = $prefix ? $prefix . 'File' : 'file';
        $lineKey = $prefix ? $prefix . 'Line' : 'line';

        if (
            isset($frame[$fileKey]) &&
            is_string($frame[$fileKey])
        ) {
            $file = (string)$frame[$fileKey];
        }

        if (
            isset($frame[$lineKey]) &&
            is_int($frame[$lineKey])
        ) {
            $line = (int)$frame[$lineKey];
        }

        if (
            $file !== null &&
            $line !== null
        ) {
            return new Location($file, $line);
        }

        return null;
    }

    public static function createClassIdentifier(
        string $class,
        ?object $object = null
    ): ClassIdentifier {
        if (str_starts_with($class, 'class@anonymous')) {
            return AnonymousClass::fromClassString($class, $object);
        }

        /** @var class-string<object> $class */
        return new NativeClass($class);
    }


    public function __construct(
        FunctionIdentifier $function,
        ArgumentList $arguments,
        ?Location $callSite = null,
        ?Location $location = null,
    ) {
        $this->function = $function;
        $this->arguments = $arguments;

        $this->callSite = $callSite;
        $this->location = $location;
    }



    public function matches(
        FunctionIdentifier|ClassIdentifier ...$identifiers
    ): bool {
        if (
            $this->function instanceof ObjectMethod ||
            $this->function instanceof StaticMethod
        ) {
            $class = $this->function->class;
        } else {
            $class = null;
        }

        foreach ($identifiers as $identifier) {
            if (
                (
                    $identifier instanceof FunctionIdentifier &&
                    $this->function->equals($identifier)
                ) ||
                (
                    $identifier instanceof ClassIdentifier &&
                    $class?->equals($identifier)
                )
            ) {
                return true;
            }
        }

        return false;
    }

    public function render(
        ?ViewOptions $options = null
    ): string {
        $options ??= new ViewOptions();
        $location = $this->callSite ?? $this->location;
        $output = $this->function->render($options);
        $output .= $this->arguments->render($options);

        if ($location !== null) {
            if ($this->function->isInternal()) {
                $prefix = '○';
            } elseif (str_contains($location->file, '/vendor/')) {
                $prefix = '◒';
            } elseif (
                $options->rootPath !== null &&
                !str_starts_with($location->file, $options->rootPath)
            ) {
                $prefix = '◐';
            } else {
                $prefix = '●';
            }

            $output = $prefix . ' ' . $output;
            $output .= "\n" . str_repeat(' ', $options->gutter) . $location->render($options);
        }

        return $output;
    }




    public function __toString(): string
    {
        return $this->render();
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerializeWithOptions(
        ?ViewOptions $options = null
    ): array {
        $options ??= new ViewOptions();

        return [
            'function' => $this->function->jsonSerializeWithOptions($options),
            'internal' => $this->function->isInternal(),
            'arguments' => $this->arguments->jsonSerializeWithOptions($options),
            'callSite' => $this->callSite?->jsonSerializeWithOptions($options),
            'location' => $this->location?->jsonSerializeWithOptions($options),
        ];
    }
}
