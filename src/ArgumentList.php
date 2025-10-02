<?php

/**
 * @package Remnant
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Remnant;

use Countable;
use Generator;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<int|string,mixed>
 */
class ArgumentList implements
    Countable,
    IteratorAggregate
{
    /**
     * @var array<int|string,mixed>
     */
    public readonly array $values;

    /**
     * @param array<int|string,mixed> $values
     */
    public function __construct(
        array $values,
        ?FunctionIdentifier $function = null
    ) {
        if ($function?->reflection !== null) {
            $params = $function->reflection->getParameters();
            $temp = $values;
            $values = [];

            foreach ($temp as $key => $value) {
                if (isset($params[$key])) {
                    $key = $params[$key]->getName();
                }

                $values[$key] = $value;
            }

            //dd2($values);
        }

        $this->values = $values;
    }

    /**
     * @return Generator<int|string,mixed>
     */
    public function getIterator(): Generator
    {
        yield from $this->values;
    }

    public function count(): int
    {
        return count($this->values);
    }

    public function render(
        ?ViewOptions $options = null
    ): string {
        $format = $options->argumentFormat ?? ArgumentFormat::Count;

        return match ($format) {
            ArgumentFormat::Count => $this->buildCountString($options),
            ArgumentFormat::InlineValues => $this->buildInlineString($options),
            ArgumentFormat::NamedValues => $this->buildNamedString($options),
        };
    }

    protected function buildCountString(
        ?ViewOptions $options = null
    ): string {
        if (($count = count($this->values)) > 0) {
            return '(...' . $count . ')';
        }

        return '()';
    }

    protected function buildInlineString(
        ?ViewOptions $options = null
    ): string {
        $output = [];

        foreach ($this->values as $value) {
            $output[] = $this->exportValue($value, $options);
        }

        return '(' . implode(', ', $output) . ')';
    }

    protected function buildNamedString(
        ?ViewOptions $options = null
    ): string {
        if (empty($this->values)) {
            return '()';
        }

        $output = [];

        foreach ($this->values as $key => $value) {
            $output[] = '        ' . $key . ': ' . $this->exportValue($value, $options);
        }

        return '(' . "\n" . implode("\n", $output) . "\n" . '    )';
    }

    protected function exportValue(
        mixed $value,
        ?ViewOptions $options = null
    ): string {
        if (is_string($value)) {
            if (strlen($value) > 16) {
                $value = substr($value, 0, 16) . '...';
            }

            $value = '\'' . $value . '\'';
        } elseif (is_array($value)) {
            if (($count = count($value)) > 0) {
                $value = '[...' . $count . ']';
            } else {
                $value = '[]';
            }
        } elseif (is_object($value)) {
            $class = Frame::createClassIdentifier(get_class($value), $value);
            $value = $class->render($options);
        } elseif (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } elseif (is_null($value)) {
            $value = 'null';
        } else {
            $value = get_debug_type($value);
        }

        return $value;
    }
}
