<?php

/**
 * Remnant
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Remnant;

use ArrayAccess;
use BadMethodCallException;
use Countable;
use DecodeLabs\Remnant\Anchor\Rewind as RewindAnchor;
use Generator;
use IteratorAggregate;
use OutOfBoundsException;
use Throwable;

use function array_map;
use function array_unshift;
use function array_values;
use function count;
use function debug_backtrace;
use function str_ends_with;
use function str_pad;
use function strlen;

use const STR_PAD_LEFT;

/**
 * @implements IteratorAggregate<int,Frame>
 * @implements ArrayAccess<int,Frame>
 */
class Trace implements
    IteratorAggregate,
    ArrayAccess,
    JsonSerializableWithOptions,
    Countable
{
    /**
     * @var array<int,Frame>
     */
    public readonly array $frames;

    public ?Location $location {
        get => $this->getFirstFrame()?->location;
    }

    public ?ViewOptions $options = null;

    public static function fromException(
        Throwable $e,
        ?Anchor $anchor = null
    ): self {
        if ($e instanceof PreparedTraceException) {
            return $e->stackTrace;
        }

        $trace = $e->getTrace();
        array_unshift($trace, [
            'callFile' => $e->getFile(),
            'callLine' => $e->getLine(),
            'function' => '__construct',
            'class' => get_class($e),
            'type' => '->',
            'args' => [
                $e->getMessage(),
                $e->getCode(),
                $e->getPrevious()
            ]
        ]);

        return self::fromDebugBacktrace($trace, $anchor);
    }

    public static function create(
        ?Anchor $anchor = null,
        ?ViewOptions $options = null
    ): self {
        if ($anchor === null) {
            $anchor = new RewindAnchor(1);
        } elseif ($anchor instanceof RewindAnchor) {
            $anchor = clone $anchor;
            $anchor->offset += 1;
        }

        // Wrap in a closure to get extra frame for callFile/Line
        return self::fromDebugBacktrace(
            (fn () => debug_backtrace())(),
            $anchor,
            $options
        );
    }

    /**
     * @param array<array<string,mixed>> $trace
     */
    public static function fromDebugBacktrace(
        array $trace,
        ?Anchor $anchor = null,
        ?ViewOptions $options = null
    ): self {
        $last = $trace[0] ?? null;
        $last['callFile'] = $last['file'] ?? null;
        $last['callLine'] = $last['line'] ?? null;
        $output = [];
        $anchorFound = $anchor ? false : true;

        foreach ($trace as $index => $frameArray) {
            // Skip Veneer proxy frames
            /** @var string $file */
            $file = $frameArray['file'] ?? '';

            if (str_ends_with(
                $file,
                'Veneer/ProxyTrait.php'
            )) {
                continue;
            }

            $frameArray['callFile'] = $frameArray['file'] ?? null;
            $frameArray['callLine'] = $frameArray['line'] ?? null;
            $frameArray['file'] = $last['callFile'];
            $frameArray['line'] = $last['callLine'];

            $frame = Frame::fromDebugBacktrace($frameArray);
            $last = $frameArray;

            if (!$anchorFound) {
                if ($anchor?->accepts($index, $frame)) {
                    $anchorFound = true;
                } else {
                    continue;
                }
            }

            $output[] = $frame;
        }

        if (empty($output)) {
            throw new OutOfBoundsException(
                'Stack is empty'
            );
        }

        return new self($output, $options);
    }


    /**
     * @param array<int,Frame> $frames
     */
    public function __construct(
        array $frames,
        ?ViewOptions $options = null
    ) {
        $this->frames = array_values($frames);
        $this->options = $options;
    }



    public function getFirstFrame(): ?Frame
    {
        return $this->frames[0] ?? null;
    }

    public function getFrame(
        int $offset
    ): ?Frame {
        return $this->frames[$offset] ?? null;
    }

    /**
     * @return Generator<int,Frame>
     */
    public function getIterator(): Generator
    {
        yield from $this->frames;
    }

    /**
     * @return array{schema:string,frames:array<array<string,mixed>>}
     */
    public function jsonSerialize(): array
    {
        return $this->jsonSerializeWithOptions($this->options);
    }

    /**
     * @return array{schema:string,frames:array<array<string,mixed>>}
     */
    public function jsonSerializeWithOptions(
        ?ViewOptions $options = null
    ): array {
        return [
            'schema' => 'remnant.trace@1',
            'frames' => array_map(fn ($frame) => $frame->jsonSerializeWithOptions($options), $this->frames)
        ];
    }

    public function count(): int
    {
        return count($this->frames);
    }

    public function render(
        ?ViewOptions $options = null
    ): string {
        $output = '';
        $options ??= $this->options ?? new ViewOptions();
        $count = $this->count() + 1;
        $pad = strlen((string)$count);
        $options->gutter = $pad + 2;
        $filtered = 0;

        foreach ($this->frames as $i => $frame) {
            $count--;

            if (
                $i > 0 &&
                !$options->filter($frame)
            ) {
                $filtered++;
                continue;
            }

            if ($filtered) {
                $output .= str_repeat(' ', $options->gutter) . '… ' . $filtered . ' hidden' . "\n\n";
                $filtered = 0;
            }

            $frameString = $frame->render($options);
            $output .= str_pad((string)$count, $pad, '0', STR_PAD_LEFT) . ': ' . $frameString . "\n\n";
        }

        return substr($output, 0, -1);
    }


    public function __toString(): string
    {
        return $this->render();
    }


    public function offsetSet(
        mixed $offset,
        mixed $value
    ): void {
        throw new BadMethodCallException('Stack traces cannot be changed after instantiation');
    }

    /**
     * @param int $offset
     */
    public function offsetGet(
        mixed $offset
    ): ?Frame {
        return $this->frames[$offset] ?? null;
    }

    /**
     * @param int $offset
     */
    public function offsetExists(
        mixed $offset
    ): bool {
        return isset($this->frames[$offset]);
    }

    /**
     * @param int $offset
     */
    public function offsetUnset(
        mixed $offset
    ): void {
        throw new BadMethodCallException('Stack traces cannot be changed after instantiation');
    }



    /**
     * @return array<int,Frame>
     */
    public function __debugInfo(): array
    {
        return $this->frames;
    }
}
