# Remnant — Package Specification

> **Cluster:** `observability`  
> **Language:** `php`  
> **Milestone:** `m1`  
> **Repo:** `https://github.com/decodelabs/remnant`  
> **Role:** Stack traces

This document describes the purpose, contracts, and design of **Remnant** within the Decode Labs ecosystem.

It is aimed at:

- Developers **using** Remnant in their own applications or libraries.
- Contributors **maintaining or extending** Remnant.
- Tools and AI assistants that need to reason about its behaviour.

---

## 1. Overview

### 1.1 Purpose

Remnant provides **easier stack traces** for PHP applications. It transforms raw `debug_backtrace()` output into:

- **Readable human-friendly traces** with clear call-sites, compact argument summaries, and visual indicators for frame types.
- **Stable JSON schema** (`remnant.trace@1`) that tools can consume programmatically.
- **Safe-by-default** argument rendering that avoids leaking sensitive values and prevents deep value dumping.
- **Path-aware formatting** that prettifies file paths using project root and package aliases (via Monarch integration).

Remnant focuses on making stack traces **useful for debugging** without overwhelming developers with noise or exposing sensitive data.

### 1.2 Non-Goals

Remnant does **not**:

- Provide logging or error reporting infrastructure (it only formats traces).
- Replace general-purpose error monitoring or observability tools.
- Implement framework-specific error pages or HTTP error responses.
- Perform deep introspection or serialization of complex object graphs (it provides compact summaries only).

Remnant is a **presentation layer** for stack traces, not a complete observability solution.

---

## 2. Role in the Ecosystem

### 2.1 Cluster & Positioning

- **Cluster:** `observability`
- Remnant is a **foundational utility** used by other Decode Labs packages (notably `decodelabs/exceptional`) to present stack traces in a consistent, readable format.

It sits at a low level in the dependency graph:

- It has minimal dependencies (optional Monarch integration).
- It is safe to use from almost anywhere in the stack.
- Higher-level packages (exception handlers, logging, debugging tools) use Remnant to:
  - format exception traces,
  - generate readable debug output,
  - produce structured trace data for tooling.

### 2.2 Typical Usage Contexts

Typical places Remnant appears:

- **Exception handling** (e.g., `exceptional` package) formatting exception stack traces.
- **Debugging tools** and development utilities that need to display call stacks.
- **CLI and HTTP runtimes** that want to present errors with readable traces.
- **Testing frameworks** that need to format assertion failures or test errors.

Remnant is intended to be used whenever a Decode Labs package needs to:

- display a stack trace to a developer,
- serialize trace data for tooling,
- filter or customize trace output.

---

## 3. Public Surface

> This section focuses on the conceptual API, not every symbol.

### 3.1 Key Types

The primary public types are:

- `DecodeLabs\Remnant\Trace`
  Main entry point for creating and working with stack traces. Implements `IteratorAggregate`, `ArrayAccess`, `Countable`, and `JsonSerializable`.

- `DecodeLabs\Remnant\Frame`
  Represents a single stack frame with function identifier, arguments, call site, and location. Implements `Stringable` and `JsonSerializable`.

- `DecodeLabs\Remnant\ViewOptions`
  Configuration object for customizing trace rendering (filters, argument format, path options, redaction rules).

- `DecodeLabs\Remnant\Location`
  Represents a file location (file path and line number) with prettification support.

- `DecodeLabs\Remnant\ArgumentList`
  Represents function arguments with safe rendering (no deep dumping).

- `DecodeLabs\Remnant\FunctionIdentifier`
  Interface for identifying functions (methods, closures, global functions, etc.).

- `DecodeLabs\Remnant\ClassIdentifier`
  Interface for identifying classes (including anonymous classes).

- `DecodeLabs\Remnant\Anchor`
  Interface for anchoring/rewinding traces to specific points.

- `DecodeLabs\Remnant\Filter`
  Interface for filtering frames from trace output.

### 3.2 Main Entry Points

The main usage pattern is creating a `Trace` via static factory methods:

```php
use DecodeLabs\Remnant\Trace;

// Create trace from current call stack
$trace = Trace::create();

// Create trace from an exception
$trace = Trace::fromException($e);

// Create trace from debug_backtrace() array
$trace = Trace::fromDebugBacktrace($backtrace);
```

Traces can be:

- **Rendered as strings** via `__toString()` or `render()`.
- **Serialized as JSON** via `json_encode()` (produces `remnant.trace@1` schema).
- **Iterated** as arrays of `Frame` objects.
- **Filtered and customized** via `ViewOptions`.

---

## 4. Dependencies

### 4.1 Direct Decode Labs Dependencies

From `composer.json`:

- None (Remnant has no required Decode Labs dependencies).

**Optional integration:**

- `decodelabs/monarch` (optional)

  Remnant detects Monarch at runtime (if installed) and uses it to prettify file
  paths (project root, package aliases, etc.). Composer declares a conflict with
  `decodelabs/monarch` versions `<0.2` to avoid known-incompatible releases.

### 4.2 External Dependencies

Remnant is designed to be **dependency-free** except for PHP itself, with optional graceful integration with Monarch for enhanced path formatting.

---

## 5. Behaviour & Contracts

### 5.1 Invariants

- `Trace::create()` **always returns a `Trace`** containing at least one frame (throws `OutOfBoundsException` if the stack is empty after anchoring).
- `Trace` objects are **immutable** after construction (frames array is readonly, `ArrayAccess` write operations throw `BadMethodCallException`).
- `Frame` objects are **immutable value objects** (all properties are readonly).
- JSON serialization **always includes** the `"schema": "remnant.trace@1"` field.
- String rendering **never dumps deep values** (arrays show count, objects show class name, strings are truncated).
- Sensitive values are **redacted by default** (password, secret, secretKey, token) unless custom redaction is provided.

### 5.2 Input & Output Contracts

- `Trace::create(?Anchor $anchor, ?ViewOptions $options)` accepts:
  - `$anchor`: Optional anchor to rewind the trace (defaults to `Rewind(1)` to skip the `Trace::create()` frame itself).
  - `$options`: Optional view options for rendering.

- `Trace::fromException(Throwable $e, ?Anchor $anchor)` accepts:
  - `$e`: Any throwable. If it implements `PreparedTraceException`, returns its pre-computed trace.
  - `$anchor`: Optional anchor to rewind the trace.

- `Trace::fromDebugBacktrace(array $trace, ?Anchor $anchor, ?ViewOptions $options)` accepts:
  - `$trace`: Raw array from `debug_backtrace()`.
  - `$anchor`: Optional anchor to rewind the trace.
  - `$options`: Optional view options.

- String rendering produces **human-readable output** with:
  - Frame numbers (counted from bottom up).
  - Visual indicators (`●` project root, `◐` symlinked package, `◒` vendor, `○` internal).
  - Function names with argument summaries.
  - Call-site locations (file:line).

- JSON serialization produces **stable schema** with:
  - `schema` field (`"remnant.trace@1"`).
  - `frames` array, each containing:
    - `function`: Function identifier (string or object).
    - `internal`: Boolean indicating if frame is internal/engine.
    - `arguments`: Object keyed by argument name (or `arg#N`).
    - `callSite`: Location where call originated (may be `null`).
    - `location`: Location where frame executed (may be `null`).

---

## 6. Error Handling

### 6.1 Exception Types

Remnant throws standard PHP exceptions:

- `OutOfBoundsException`: When trace creation results in an empty stack (e.g., anchor rewinds past all frames).
- `BadMethodCallException`: When attempting to modify a trace via `ArrayAccess::offsetSet()` or `offsetUnset()`.
- `InvalidArgumentException`: When `ViewOptions` receives invalid parameters (e.g., `maxStringLength` out of range).

### 6.2 Error Strategy

Remnant itself does **not** use `decodelabs/exceptional` (it has no dependencies). It throws standard SPL exceptions for error conditions.

Remnant is designed to be used **by** exception handling systems (like `exceptional`) to format exception traces, not to handle errors itself.

---

## 7. Configuration & Extensibility

### 7.1 Configuration

Remnant is configured via `ViewOptions`:

- `rootPath`: Project root path for path prettification (auto-detected from Monarch if available).
- `filters`: Array of `Filter` implementations to exclude frames from output.
- `argumentFormat`: How arguments are rendered (`Count`, `InlineValues`, or `NamedValues`).
- `maxStringLength`: Maximum string length before truncation (1-100, default 16).
- `redact`: Closure or `true` for default redaction (password, secret, secretKey, token).
- `collapseSingleLineArguments`: Whether to collapse single-argument frames to one line.
- `absolutePaths`: Whether to include absolute paths in JSON output (default `false`).

### 7.2 Extension Points

Remnant supports extension via:

- **Custom `Filter` implementations**: Filter frames by path, function, class, namespace, or vendor.
- **Custom `Anchor` implementations**: Rewind traces based on custom criteria (class, function, or offset).
- **Custom redaction closures**: Control which argument values are redacted.

Built-in filters include:

- `Filter\Vendor`: Hide frames under `vendor/`.
- `Filter\Paths`: Hide frames matching path prefixes.
- `Filter\FunctionIdentifier`: Hide specific functions.
- `Filter\ClassIdentifier`: Hide specific classes.
- `Filter\NamespaceIdentifier`: Hide specific namespaces.

Built-in anchors include:

- `Anchor\Rewind`: Rewind by fixed number of frames.
- `Anchor\ClassIdentifier`: Rewind to last frame of a class.
- `Anchor\FunctionIdentifier`: Rewind to last frame of a function.

---

## 8. Interactions with Other Packages

Remnant is designed to be used by other packages:

- **`decodelabs/exceptional`**
  Uses Remnant to format exception stack traces. Exceptions can implement `PreparedTraceException` to provide pre-computed traces.

- **`decodelabs/monarch`** (optional)
  Remnant detects Monarch at runtime and uses it for path prettification. If Monarch is not available, Remnant falls back to basic path formatting.

Design assumptions:

- Remnant is available early in the stack and is considered **safe to use from any layer**.
- Other packages should not override Remnant's core mechanisms, but may:
  - implement custom filters and anchors,
  - provide `PreparedTraceException` implementations,
  - configure `ViewOptions` for their use cases.

---

## 9. Usage Examples

### 9.1 Basic trace creation

```php
use DecodeLabs\Remnant\Trace;

$trace = Trace::create();
echo (string)$trace;
```

### 9.2 Trace from exception

```php
use DecodeLabs\Remnant\Trace;

try {
    // ... code that throws ...
} catch (\Throwable $e) {
    $trace = Trace::fromException($e);
    echo (string)$trace;
}
```

### 9.3 JSON serialization

```php
use DecodeLabs\Remnant\Trace;

$trace = Trace::create();
$json = json_encode($trace, JSON_PRETTY_PRINT);
// Produces: {"schema": "remnant.trace@1", "frames": [...]}
```

### 9.4 Custom view options

```php
use DecodeLabs\Remnant\ArgumentFormat;
use DecodeLabs\Remnant\Trace;
use DecodeLabs\Remnant\ViewOptions;

$trace = Trace::create(options: new ViewOptions(
    rootPath: '/path/to/project',
    argumentFormat: ArgumentFormat::NamedValues,
    maxStringLength: 32,
    redact: fn (string $key, mixed $value) => $key === 'apiKey',
    absolutePaths: true
));
```

### 9.5 Filtering frames

```php
use DecodeLabs\Remnant\Filter;
use DecodeLabs\Remnant\Trace;
use DecodeLabs\Remnant\ViewOptions;

$view = new ViewOptions(
    filters: [
        new Filter\Vendor(),
        new Filter\Paths(['/path/to/filter/']),
    ]
);

echo Trace::create(options: $view);
```

### 9.6 Anchoring traces

```php
use DecodeLabs\Remnant\Anchor;
use DecodeLabs\Remnant\FunctionIdentifier\ObjectMethod;
use DecodeLabs\Remnant\Trace;

// Rewind to skip frames up to a specific method
$trace = Trace::create(
    new Anchor\FunctionIdentifier(
        new ObjectMethod(MyClass::class, 'myMethod')
    )
);
```

### 9.7 Accessing frames

```php
use DecodeLabs\Remnant\Trace;

$trace = Trace::create();

foreach ($trace as $frame) {
    echo $frame->function->name . ' at ' . $frame->location . PHP_EOL;
}

$firstFrame = $trace[0];
echo (string)$firstFrame;
```

---

## 10. Implementation Notes (For Contributors)

### 10.1 Internal Architecture

At a high level, Remnant:

- **Parses `debug_backtrace()` arrays** into structured `Frame` objects.
- **Identifies functions and classes** using reflection where available, falling back to string parsing.
- **Renders arguments safely** by:
  - Using reflection to map positional arguments to parameter names.
  - Truncating strings, showing array counts, and displaying object class names.
  - Applying redaction rules before rendering.
- **Prettifies paths** by:
  - Detecting Monarch at runtime (if available).
  - Using project root to distinguish project vs vendor vs package paths.
  - Generating package aliases (`@pkg:name`) for symlinked packages.
- **Filters frames** before rendering based on `ViewOptions` filters.

Key implementation details:

- `Trace` wraps frames in a readonly array and provides array-like access.
- `Frame` uses `FunctionIdentifier` and `ClassIdentifier` interfaces to abstract different function/class types.
- `Location` handles eval'd code detection and path normalization.
- `ArgumentList` uses reflection to map arguments to parameter names when available.

### 10.2 Performance Considerations

- Reflection is used **lazily** (only when needed for argument name mapping or function identification).
- Path prettification checks for Monarch **at runtime** (no hard dependency).
- Filtering happens **during rendering**, not during trace creation (filters can be applied multiple times with different `ViewOptions`).
- JSON serialization is **efficient** (no deep copying, direct array mapping).

### 10.3 Gotchas & Historical Decisions

- **Veneer proxy frames** are automatically skipped (frames from `Veneer/ProxyTrait.php` are filtered out).
- **Eval'd code** is detected via path pattern matching and tracked separately (`evalLine` field).
- **Anonymous classes** are identified via `class@anonymous` prefix and parsed to extract defining file/line.
- **Closures** include their defining file/line in their identifier.
- **Call-site vs location**: `callSite` is where the call originated, `location` is where the frame executed (may differ for closures/eval).

---

## 11. Testing & Quality

### 11.1 Testing Strategy

Tests should cover:

- Trace creation from `debug_backtrace()`, exceptions, and current stack.
- Frame parsing for different function types (methods, closures, global functions, etc.).
- Argument rendering with different formats (`Count`, `InlineValues`, `NamedValues`).
- Path prettification with and without Monarch.
- Filter application (vendor, paths, identifiers).
- Anchor rewinding (offset, class, function).
- JSON serialization schema compliance.
- Redaction rules (default and custom).
- Edge cases (empty traces, missing file/line, eval'd code, anonymous classes).

### 11.2 Quality Signals

From the Decode Labs package index (at time of writing):

- **Code:** 5.0
- **Readme:** 5.0
- **Docs:** (spec evolving)
- **Tests:** (to be updated as coverage grows)

Remnant is a **high-quality, dependency-free utility** that should be treated as a stable foundation for trace formatting across the Decode Labs ecosystem.

---

## 12. Roadmap & Future Ideas

Non-binding ideas:

- Additional filter types (e.g., regex-based path matching, custom predicate filters).
- Enhanced path prettification (e.g., source map support for compiled code).
- Performance optimizations for large traces (e.g., lazy frame parsing).
- Integration with observability tools (e.g., OpenTelemetry trace export).
- Additional argument format options (e.g., structured object summaries).

---

## 13. References

- **Chorus docs:**
  - Architecture principles
  - Package taxonomy & clusters
  - Backwards compatibility strategy (once published)

- **Related packages:**
  - `decodelabs/exceptional` (uses Remnant for trace formatting)
  - `decodelabs/monarch` (optional integration for path prettification)

- **Repository:**
  - `https://github.com/decodelabs/remnant`

