# Remnant — Package Specification

> **Cluster:** `observability`
> **Language:** `php`
> **Milestone:** `m1`
> **Repo:** `https://github.com/decodelabs/remnant`
> **Role:** Stack traces

## Overview

### Purpose

Remnant provides easier stack traces with a clean, readable view for humans and a stable JSON schema for tools. It avoids leaking sensitive values by default and lets you hide noisy bits when needed.

Key features:
- **Readable traces** with clear call-sites and compact argument summaries
- **Stable JSON** that tools can consume
- **Safe by default** – no deep value dumping, sensitive values are redacted
- **Root / package aware** – paths are prettified and simplified where possible
- **Tiny & modern** – PHP 8.4+, property getters and readonly value objects

### Non-Goals

- Remnant does not provide exception handling or error reporting functionality.
- It does not integrate with logging systems or error tracking services.
- It does not provide performance profiling or execution timing.
- It does not handle error recovery or retry mechanisms.
- It does not provide debugging breakpoints or step-through debugging.

## Role in the Ecosystem

### Cluster & Positioning

Remnant belongs to the **observability** cluster, focusing on debugging and inspection capabilities. It complements other observability packages like `nuance` (dump inspector) and `glitch` (error handling) by providing structured stack trace analysis.

### Usage Contexts

- **Debugging**: Generating readable stack traces for debugging purposes
- **Error reporting**: Creating structured trace data for error tracking systems
- **Logging**: Including formatted traces in log output
- **Development tools**: Providing trace data for IDE integration or debugging tools
- **Exception handling**: Converting exceptions to structured trace data

## Public Surface

### Key Types

- **`Trace`** (class): Main entry point for creating and managing stack traces. Implements `IteratorAggregate`, `ArrayAccess`, `JsonSerializableWithOptions`, and `Countable`. Provides methods for creating traces from current execution or exceptions.

- **`Frame`** (class): Represents a single stack frame with function identifier, arguments, call site, and location. Implements `JsonSerializableWithOptions` and `Stringable`.

- **`ViewOptions`** (class): Configuration for customizing trace output, including filters, argument formatting, path display, and redaction rules.

- **`Location`** (class): Represents a file location with line number and optional eval line. Implements `JsonSerializableWithOptions` and `Stringable`.

- **`ArgumentList`** (class): Manages function arguments with name resolution and formatting. Implements `Countable`, `IteratorAggregate`, and `JsonSerializableWithOptions`.

- **`Anchor`** (interface): Defines anchors for rewinding traces to specific points. Implementations include `Anchor\Rewind`, `Anchor\ClassIdentifier`, and `Anchor\FunctionIdentifier`.

- **`Filter`** (interface): Defines filters for excluding frames from trace output. Implementations include `Filter\Vendor`, `Filter\Paths`, `Filter\FunctionIdentifier`, `Filter\ClassIdentifier`, and `Filter\NamespaceIdentifier`.

- **`FunctionIdentifier`** (interface): Identifies functions with various implementations: `FunctionIdentifier\GlobalFunction`, `FunctionIdentifier\NamespaceFunction`, `FunctionIdentifier\ObjectMethod`, `FunctionIdentifier\StaticMethod`, `FunctionIdentifier\Closure`, `FunctionIdentifier\ClassTrait`.

- **`ClassIdentifier`** (interface): Identifies classes with implementations: `ClassIdentifier\Native` and `ClassIdentifier\Anonymous`.

- **`ArgumentFormat`** (enum): Defines argument display formats: `Count`, `InlineValues`, `NamedValues`.

### Main Entry Points

**Trace Creation:**
- `Trace::create(?Anchor $anchor = null, ?ViewOptions $options = null): Trace` — Create trace from current execution point
- `Trace::fromException(Throwable $e, ?Anchor $anchor = null): Trace` — Create trace from exception
- `Trace::fromDebugBacktrace(array $trace, ?Anchor $anchor = null, ?ViewOptions $options = null): Trace` — Create trace from debug_backtrace() array

**Trace Access:**
- `$trace->frames` — Readonly array of Frame instances
- `$trace->location` — Location of first frame (readonly property)
- `$trace->getFirstFrame(): ?Frame` — Get the first frame
- `$trace->getFrame(int $offset): ?Frame` — Get frame by index
- `$trace[0]` — Array access to frames (returns `?Frame`)
- `foreach ($trace as $frame)` — Iterator access
- `count($trace)` — Count frames

**Trace Output:**
- `(string)$trace` — String conversion returns formatted trace
- `$trace->render(?ViewOptions $options = null): string` — Render trace with optional options
- `json_encode($trace)` — JSON serialization with stable schema

**Frame:**
- `Frame::create(int $rewind = 0): Frame` — Create single frame from current execution
- `Frame::fromDebugBacktrace(array $frame): Frame` — Create frame from debug_backtrace() entry
- `$frame->function` — FunctionIdentifier (readonly)
- `$frame->arguments` — ArgumentList (readonly)
- `$frame->callSite` — Location where call originated (readonly, nullable)
- `$frame->location` — Location where frame executed (readonly, nullable)
- `$frame->matches(FunctionIdentifier|ClassIdentifier ...$identifiers): bool` — Check if frame matches identifiers
- `(string)$frame` — String conversion returns formatted frame

**ViewOptions:**
- `new ViewOptions(?string $rootPath = null, array $filters = [], ArgumentFormat $argumentFormat = ArgumentFormat::NamedValues, int $maxStringLength = 16, int $gutter = 4, Closure|true|null $redact = true, bool $collapseSingleLineArguments = false, bool $absolutePaths = false)` — Constructor
- `$options->filter(Frame $frame): bool` — Check if frame passes filters

**Anchors:**
- `new Anchor\Rewind(int $offset)` — Rewind trace by number of frames
- `new Anchor\FunctionIdentifier(FunctionIdentifier $identifier)` — Rewind to function
- `new Anchor\ClassIdentifier(ClassIdentifier|string $identifier)` — Rewind to class

**Filters:**
- `new Filter\Vendor()` — Filter vendor directory frames
- `new Filter\Paths(array $paths)` — Filter by path prefixes
- `new Filter\FunctionIdentifier(FunctionIdentifier $identifier)` — Filter by function
- `new Filter\ClassIdentifier(ClassIdentifier|string $identifier)` — Filter by class
- `new Filter\NamespaceIdentifier(string|ClassIdentifier $identifier)` — Filter by namespace

## Dependencies

### Decode Labs

None. Remnant has no Decode Labs dependencies.

### External

- **PHP**: See `composer.json` for supported PHP versions.

### Optional

- **`decodelabs/monarch`**: Detected at runtime if installed, used for path prettification and root path detection. If available, paths are prettified using Monarch's path aliases, and the root path is automatically detected.

## Behaviour & Contracts

### Invariants

- A `Trace` instance is immutable after construction (frames array is readonly).
- Frames are indexed starting from 0, matching `debug_backtrace()` order.
- Negative or out-of-range frame indices return `null`.
- String output uses consistent formatting with visual indicators for frame types.
- JSON output includes schema tag `"remnant.trace@1"` for versioning.

### Input & Output Contracts

**Trace Creation:**
- `create()` defaults to rewinding 1 frame (excluding the `create()` call itself).
- `fromException()` handles `PreparedTraceException` specially, returning its pre-computed trace.
- Anchors rewind the trace until the anchor condition is met.
- Empty traces after anchoring throw `OutOfBoundsException`.

**Frame Rendering:**
- Frames use visual indicators: `●` (project root), `◐` (symlinked package), `◒` (vendor), `○` (internal).
- Call-site is shown on line below function signature.
- Arguments are formatted according to `ArgumentFormat` setting.
- Long strings are truncated with ellipsis and length indicator.

**Path Prettification:**
- Paths use `@root:` prefix for project root.
- Paths use `@pkg:name:` prefix for symlinked packages.
- Absolute paths are opt-in via `ViewOptions`.
- Monarch integration provides additional prettification if available.

**Argument Formatting:**
- `Count`: Shows only argument count, e.g., `(...3)`
- `InlineValues`: Shows values inline, e.g., `('value', 123, object(Class))`
- `NamedValues`: Shows named arguments with values, e.g., `(\n    name: 'value'\n)`

**Redaction:**
- Default redaction covers: `password`, `secret`, `secretKey`, `token`.
- Custom redaction via closure: `fn (string $key, mixed $value): bool`.
- Redacted values shown as `⟪redacted⟫`.

## Error Handling

- **Empty stack**: `Trace::fromDebugBacktrace()` throws `OutOfBoundsException` if trace is empty after anchoring.
- **Invalid string length**: `ViewOptions` constructor throws `InvalidArgumentException` if `maxStringLength` is not between 1 and 100.
- **Array access modification**: Attempting to modify trace via array access throws `BadMethodCallException`.
- **Frame access**: Out-of-range frame access returns `null` (does not throw).

## Configuration & Extensibility

### Custom Anchors

Implement the `Anchor` interface to create custom anchoring logic:

```php
class CustomAnchor implements Anchor
{
    public function accepts(int $offset, Frame $frame): bool
    {
        // Return true when anchor point is reached
    }
}
```

### Custom Filters

Implement the `Filter` interface to create custom filtering logic:

```php
class CustomFilter implements Filter
{
    public function accepts(Frame $frame): bool
    {
        // Return false to exclude frame
    }
}
```

### Custom Function Identifiers

Implement the `FunctionIdentifier` interface for custom function identification (rarely needed, existing implementations cover most cases).

### Custom Class Identifiers

Implement the `ClassIdentifier` interface for custom class identification (rarely needed, existing implementations cover most cases).

## Interactions with Other Packages

- **Monarch**: Optional integration for path prettification and root path detection. If available, automatically used for prettifying file paths.
- **Exceptional**: Remnant traces can be created from exceptions, and `PreparedTraceException` provides pre-computed traces.
- **Nuance**: Remnant traces can be used alongside Nuance for comprehensive debugging output.
- **Glitch**: Remnant traces can be integrated with Glitch for enhanced error handling.

## Usage Examples

### Basic Trace Creation

```php
use DecodeLabs\Remnant\Trace;

// Create trace from current execution
$trace = Trace::create();
echo $trace;

// Create trace from exception
try {
    // ...
} catch (\Exception $e) {
    $trace = Trace::fromException($e);
    echo $trace;
}
```

### Custom View Options

```php
use DecodeLabs\Remnant\ArgumentFormat;
use DecodeLabs\Remnant\ViewOptions;

$options = new ViewOptions(
    rootPath: '/path/to/project',
    argumentFormat: ArgumentFormat::NamedValues,
    maxStringLength: 32,
    redact: fn (string $key, mixed $value) => $key === 'password',
    absolutePaths: false
);

$trace = Trace::create(options: $options);
echo $trace;
```

### Using Anchors

```php
use DecodeLabs\Remnant\Anchor\Rewind;
use DecodeLabs\Remnant\Anchor\ClassIdentifier;
use DecodeLabs\Remnant\FunctionIdentifier\ObjectMethod;
use DecodeLabs\Remnant\Trace;

// Rewind by number of frames
$trace = Trace::create(new Rewind(2));

// Rewind to specific function
$trace = Trace::create(
    new Anchor\FunctionIdentifier(
        new ObjectMethod(MyClass::class, 'myMethod')
    )
);

// Rewind to specific class
$trace = Trace::create(
    new ClassIdentifier(MyClass::class)
);
```

### Using Filters

```php
use DecodeLabs\Remnant\Filter;
use DecodeLabs\Remnant\FunctionIdentifier\ObjectMethod;
use DecodeLabs\Remnant\Trace;
use DecodeLabs\Remnant\ViewOptions;

$options = new ViewOptions(
    filters: [
        new Filter\Vendor(),
        new Filter\Paths(['/path/to/filter/']),
        new Filter\FunctionIdentifier(
            new ObjectMethod(SomeClass::class, 'someMethod')
        ),
        new Filter\ClassIdentifier(SomeClass::class),
        new Filter\NamespaceIdentifier('Some\\Namespace'),
    ]
);

$trace = Trace::create(options: $options);
echo $trace;
```

### Accessing Frames

```php
use DecodeLabs\Remnant\Trace;

$trace = Trace::create();

// Iterate frames
foreach ($trace as $frame) {
    echo $frame->function . ' at ' . $frame->location . PHP_EOL;
}

// Access by index
$firstFrame = $trace[0];
$secondFrame = $trace->getFrame(1);

// Check frame matches
if ($firstFrame->matches($someFunctionIdentifier)) {
    // ...
}
```

### JSON Output

```php
use DecodeLabs\Remnant\Trace;

$trace = Trace::create();
$json = json_encode($trace, JSON_PRETTY_PRINT);

// Schema: "remnant.trace@1"
// Includes: frames array with function, internal, arguments, callSite, location
```

## Implementation Notes (for Contributors)

### Frame Processing

- Frames are processed from `debug_backtrace()` output.
- Veneer proxy frames are automatically skipped.
- Call-site information is derived from the previous frame's file/line.
- Function identifiers are created based on frame type (static method, object method, namespace function, global function, closure).

### Argument Resolution

- Argument names are resolved using reflection when available.
- Unknown argument names use `arg#N` format.
- Arguments are stored with resolved names for better JSON output.

### Path Prettification

- Paths are normalized to forward slashes.
- Package detection searches up directory tree for `composer.json`.
- Monarch integration provides additional prettification if available.
- Eval'd code paths are parsed to extract eval line information.

### Rendering

- String rendering uses consistent formatting with visual indicators.
- Filtered frames are shown as `… N hidden` when consecutive frames are filtered.
- Frame numbers are zero-padded for alignment.
- Argument formatting respects `ArgumentFormat` and `collapseSingleLineArguments` settings.

### JSON Schema

- Schema version is `"remnant.trace@1"`.
- All value objects implement `JsonSerializableWithOptions` for consistent serialization.
- Absolute paths are opt-in via `ViewOptions`.
- Schema is stable across versions for tool consumption.

## Testing & Quality

**Current Status:**
- Code quality: 5/5
- README quality: 5/5
- Documentation: 0/5 (no formal docs yet)
- Tests: 0/5 (no test suite yet)

**Testing Considerations:**
- Trace creation should be tested for:
  - Current execution traces
  - Exception traces
  - Debug backtrace array conversion
  - Anchor rewinding behavior
  - Empty trace handling

- Frame processing should be tested for:
  - Various function types (static, object, namespace, global, closure)
  - Anonymous class handling
  - Argument name resolution
  - Call-site extraction
  - Location parsing

- ViewOptions should be tested for:
  - Filter application
  - Argument formatting
  - Path prettification
  - Redaction rules
  - Edge cases (empty filters, null values, etc.)

- Rendering should be tested for:
  - String output formatting
  - JSON schema compliance
  - Visual indicators
  - Filtered frame display
  - Edge cases (empty traces, single frames, etc.)

## Roadmap & Future Ideas

- **Performance profiling**: Integration with execution timing data
- **Source code context**: Include source code snippets in traces
- **Variable inspection**: Enhanced variable value inspection (with privacy controls)
- **Trace comparison**: Utilities for comparing traces
- **IDE integration**: Better integration with IDEs and debugging tools
- **Trace serialization**: More efficient serialization formats
- **Trace storage**: Utilities for storing and retrieving traces
- **Trace analysis**: Tools for analyzing trace patterns

## References

- Package repository: https://github.com/decodelabs/remnant
- Composer package: https://packagist.org/packages/decodelabs/remnant
- Related packages:
  - `decodelabs/monarch` — Path management (optional, for prettification)
  - `decodelabs/exceptional` — Exception handling (can create traces from exceptions)
  - `decodelabs/nuance` — Debugging and inspection (complementary)
  - `decodelabs/glitch` — Error handling (can integrate traces)
