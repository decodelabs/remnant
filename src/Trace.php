<?php

/**
 * @package Remnant
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Remnant;

use ArrayAccess;
use ArrayIterator;
use BadMethodCallException;
use Countable;
use DecodeLabs\Remnant\Anchor\Rewind as RewindAnchor;
use IteratorAggregate;
use JsonSerializable;
use OutOfBoundsException;
use Throwable;
use Traversable;

/**
 * @implements IteratorAggregate<int,Frame>
 * @implements ArrayAccess<int,Frame>
 */
class Trace implements
    IteratorAggregate,
    ArrayAccess,
    JsonSerializable,
    Countable
{
    /**
     * @var array<int,Frame>
     */
    public protected(set) array $frames = [];

    public ?Location $location {
        get => $this->getFirstFrame()?->location;
    }

    public static function fromException(
        Throwable $e,
        ?Anchor $anchor = null
    ): self {
        if ($e instanceof PreparedTraceException) {
            return $e->stackTrace;
        }

        $output = self::fromDebugBacktrace($e->getTrace(), $anchor);

        array_unshift($output->frames, Frame::fromDebugBacktrace([
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
        ]));

        return $output;
    }

    public static function create(
        ?Anchor $anchor = null
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
            $anchor
        );
    }

    /**
     * @param array<array<string,mixed>> $trace
     */
    public static function fromDebugBacktrace(
        array $trace,
        ?Anchor $anchor = null
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

        return new self(...$output);
    }


    public function __construct(
        Frame ...$frames
    ) {
        foreach ($frames as $frame) {
            $this->frames[] = $frame;
        }
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

    public function shift(): ?Frame
    {
        return array_shift($this->frames);
    }



    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->frames);
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function jsonSerialize(): array
    {
        return array_map(function ($frame) {
            return $frame->jsonSerialize();
        }, $this->frames);
    }

    public function count(): int
    {
        return count($this->frames);
    }

    public function render(
        ?ViewOptions $options = null
    ): string {
        $output = '';
        $count = $this->count() + 1;
        $pad = strlen((string)$count);

        foreach ($this->frames as $frame) {
            $count--;

            if (!$options?->filter($frame)) {
                continue;
            }

            $frameString = $frame->render($options);
            $output .= str_pad((string)$count, $pad, ' ', \STR_PAD_LEFT) . ': ' . $frameString . "\n";
        }

        return $output;
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
