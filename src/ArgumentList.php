<?php

/**
 * @package Remnant
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Remnant;

use Countable;
use DateTimeInterface;
use Generator;
use IteratorAggregate;
use JsonSerializable;

/**
 * @implements IteratorAggregate<int|string,mixed>
 */
class ArgumentList implements
    Countable,
    IteratorAggregate,
    JsonSerializable
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
        $options ??= new ViewOptions();

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
        $options ??= new ViewOptions();

        foreach ($this->values as $key => $value) {
            $string = $this->exportValue($key, $value, $options);

            if (!array_is_list($this->values)) {
                $string = $key . ': ' . $string;
            }

            $output[] = $string;
        }

        if (count($output) === 1) {
            return '(' . $output[0] . ')';
        }

        $pad = str_repeat(' ', $options->gutter + 4);

        return
            '(' . "\n" .
            $pad . implode("\n" . $pad, $output) . "\n" .
            str_repeat(' ', $options->gutter) . ')';
    }

    protected function exportValue(
        string|int $key,
        mixed $value,
        ?ViewOptions $options = null
    ): string {
        $options ??= new ViewOptions();

        if ($options->redact?->__invoke($key, $value)) {
            return '*sensitive*';
        }

        if (is_string($value)) {
            if (($length = mb_strlen($value)) > $options->maxStringLength) {
                $truncated = true;
                $value = mb_substr($value, 0, $options->maxStringLength) . '...';
            } else {
                $truncated = false;
            }

            $value = '\'' . $value . '\'';

            if ($truncated) {
                $value .= '[' . $length . ']';
            }
        } elseif (is_array($value)) {
            $value = 'array(' . count($value) . ')';
        } elseif ($value instanceof DateTimeInterface) {
            $value = $value->format('Y-m-d H:i:s');
        } elseif (is_object($value)) {
            $class = Frame::createClassIdentifier(get_class($value), $value);
            $value = $class->render($options);
        } elseif (is_resource($value)) {
            $value = '{resource ' . get_resource_type($value) . '}';
        } elseif (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } elseif (is_null($value)) {
            $value = 'null';
        } else {
            $value = get_debug_type($value);
        }

        return $value;
    }

    /**
     * @return array<int|string,mixed>
     */
    public function jsonSerialize(): array
    {
        $options = new ViewOptions();
        $output = [];

        foreach ($this->values as $key => $value) {
            if (
                $value === null ||
                is_bool($value) ||
                is_int($value) ||
                is_float($value)
            ) {
                $output[$key] = $value;
            } else {
                $output[$key] = $this->exportValue($key, $value, $options);
            }
        }

        return $output;
    }
}
