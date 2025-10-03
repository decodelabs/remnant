# Remnant

[![PHP from Packagist](https://img.shields.io/packagist/php-v/decodelabs/remnant?style=flat)](https://packagist.org/packages/decodelabs/remnant)
[![Latest Version](https://img.shields.io/packagist/v/decodelabs/remnant.svg?style=flat)](https://packagist.org/packages/decodelabs/remnant)
[![Total Downloads](https://img.shields.io/packagist/dt/decodelabs/remnant.svg?style=flat)](https://packagist.org/packages/decodelabs/remnant)
[![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/decodelabs/remnant/integrate.yml?branch=develop)](https://github.com/decodelabs/remnant/actions/workflows/integrate.yml)
[![PHPStan](https://img.shields.io/badge/PHPStan-enabled-44CC11.svg?longCache=true&style=flat)](https://github.com/phpstan/phpstan)
[![License](https://img.shields.io/packagist/l/decodelabs/remnant?style=flat)](https://packagist.org/packages/decodelabs/remnant)

### Easier stack traces

Remnant gives you a clean, readable view for humans and a stable JSON schema for tools. It avoids leaking sensitive values by default and lets you hide the noisy bits when you want to.

- **Readable traces** with clear call-sites and a compact argument summary
- **Stable JSON** that tools can consume
- **Safe by default** – no deep value dumping, sensitive values are redacted
- **Root / package aware** – paths are prettified and simplified where possible
- **Tiny & modern** – PHP 8.4+, property getters and readonly value objects

---

## Installation

#### Requirements
- PHP **8.4+**

Install via Composer:

```bash
composer require decodelabs/remnant
```

## Quick start

```php
use DecodeLabs\Remnant\Trace;

$trace = Trace::create();                 // from here
// or: $trace = Trace::fromException($e); // from a Throwable

echo (string)$trace;                      // pretty string output
$json = json_encode($trace, JSON_PRETTY_PRINT); // stable JSON
```

### String output

Example:

```
24: ● DecodeLabs\Remnant\Trace::create()
    @root:/src/@components/pages/index.php:28
23: ● {anonymous:@root:/src/@components/pages/index.php:25}->__construct(
        timer: object(DecodeLabs\Kairos\Timer)
    )
    @root:/src/@components/pages/index.php:25
22: ● {closure:@root:/src/@components/pages/index.php:24}()
    @root:/src/@components/pages/index.php:47 [eval:1]
21: ○ [internal] eval()
    @root:/src/@components/pages/index.php:47
20: ◐ {closure:@root:/src/@components/pages/index.php:21}(
        arg#0: object(DecodeLabs\Lucid)
    )
    @pkg:slingshot/src/Slingshot.php:500
…
02: ◒ DecodeLabs\Genesis->run()
    @root:/vendor/genesis.php:25
01: ○ [internal] require(
        arg#0: '/path/to/...'[64]
    )
    @pkg:valet/server.php:167
```

#### Legend

- `●` – frame within **project root** (`@root:`)
- `◐` – frame outside of the **project root** (e.g. a **symlinked package**) (`@pkg:name:`)
- `◒` – frame under **root vendor** (`@root:/vendor/...`)
- `○` – **internal/engine** frame

#### Conventions

- Call-site (file:line) is shown on the line below the function.
- Closures and anonymous classes include their defining file:line.
- Arrays print as `array(n)`.
  Objects print as `object(Fully\Qualified\Class)`.
- Unknown argument names are shown as `arg#N:` to keep order explicit.
- Sensitive values are rendered as `⟪redacted⟫`.
- Long strings are truncated with an ellipsis and the original length, e.g. `'/path/…'[64]`.
- You may see fold lines like `… 3 hidden` when filters elide frames.

### JSON output

`Trace` and frame value objects implement `JsonSerializable`.

- **Schema tag**: `"schema": "remnant.trace@1"`
- **Stable keys**; some fields may be `null` (internal frames, optimised frames, etc.)
- **Absolute paths**: opt-in via a view option (off by default)

#### Example

```json
{
  "schema": "remnant.trace@1",
  "frames": [
    {
      "function": "DecodeLabs\\Remnant\\Trace::create",
      "internal": false,
      "arguments": {},
      "callSite": {
        "file": "@root:/src/@components/pages/index.php",
        "absolute": "/Users/.../src/@components/pages/index.php",
        "line": 28
      },
      "location": {
        "file": "@pkg:remnant/src/Trace.php",
        "absolute": "/Users/.../remnant/src/Trace.php",
        "line": 90
      }
    },
    {
      "function": "eval",
      "internal": true,
      "arguments": {},
      "callSite": {
        "file": "@root:/src/@components/pages/index.php",
        "absolute": "/Users/.../src/@components/pages/index.php",
        "line": 58
      },
      "location": {
        "file": "@root:/src/@components/pages/index.php",
        "absolute": "/Users/.../src/@components/pages/index.php",
        "line": 58,
        "eval": 1
      }
    }
    // …
  ]
}
```

#### Notes

- `function` is always a string.
- `internal` is `true` for engine frames (`eval`, `require`, etc.).
- `arguments` is an object keyed by arg name (or `arg#N`).
- `callSite` is where the call originated; may be `null`.
- `location` is where the frame executed; may be `null`.
- `file` paths are prettified where possible.
- `absolute` paths can be enabled or disabled.
- `eval` marks eval’d code with its eval line.


## Arguments & privacy

Remnant **does not dump deep values**. It prints a compact, single-line summary per argument:

- Scalars are inlined (with truncation where necessary).
- Arrays → `array(n)` (count only).
- Objects → `object(FQCN)`.
- Unknown names (e.g. closure params) are labelled `arg#N`.
- Redacted values use `⟪redacted⟫`.

This keeps traces useful without leaking secrets or producing log noise.

## Path prettification

Paths are prettified where possible using [Monarch](https://github.com/decodelabs/monarch). Ensure `Monarch` is available in your project, and prepare your path aliases in your bootstrap:

```php
Monarch::getPaths()->alias('@components', '@run/src/@components');
```

## Anchors

Anchors let you rewind the trace to a specific point based on various different criteria. They allow you to filter out frames that are created by _generating_ the trace, rather than the trace itself.

```php
use DecodeLabs\Remnant\Anchor\ClassIdentifier;
use DecodeLabs\Remnant\Anchor\FunctionIdentifier;
use DecodeLabs\Remnant\Anchor\Rewind;
use DecodeLabs\Remnant\FunctionIdentifier\ObjectMethod;
use DecodeLabs\Remnant\Trace;

// Pass a rewind anchor to rewind the trace by that many frames
$trace = Trace::create(new Rewind(2));

// Pass a FunctionIdentifier anchor to rewind back to the last instance of the function
$trace = Trace::create(
    new FunctionIdentifier(
        new ObjectMethod(MyClass::class, 'myFunction')
    )
);

// Pass a ClassIdentifier anchor to rewind back to the last instance of the class
$trace = Trace::create(
    new ClassIdentifier(MyClass::class)
);
```

## View options

View options let you customise the output of the trace, all options have a reasonable default value.

```php
use DecodeLabs\Remnant\ArgumentFormat;
use DecodeLabs\Remnant\ViewOptions;

$trace = Trace::create(options: new ViewOptions(
    rootPath: '/path/to/project',
    filters: [],
    // How arguments are rendered - `Count`, `InlineValues` or `NamedValues`
    argumentFormat: ArgumentFormat::NamedValues,
    maxStringLength: 16,
    redact: fn (string $key, mixed $value) => $key === 'password',
    collapseSingleLineArguments: false,
    absolutePaths: true
));
```

## Filters

Filters let you filter out frames before rendering the trace. No filters are applied by default.

Available filters include:

- **Vendor** – hide frames under `{$projectRoot}/vendor/`
- **Paths** – prefix match against canonicalised (forward-slash) paths
- **Function / Class / Namespace identifiers** – match by function signature

Example:

```php
use DecodeLabs\Greenleaf;
use DecodeLabs\Remnant\Filter;
use DecodeLabs\Remnant\FunctionIdentifier\ObjectMethod;
use DecodeLabs\Remnant\Trace;
use DecodeLabs\Remnant\ViewOptions;

$view = new ViewOptions(
    filters: [
        new Filter\Vendor(),
        new Filter\Paths(['/path/to/filter/']),
        new Filter\FunctionIdentifier(new ObjectMethod(Greenleaf::class, 'myFunction')),
        new Filter\ClassIdentifier(Greenleaf::class),
        new Filter\NamespaceIdentifier(Greenleaf::class),
    ]
);

echo Trace::create(options: $view);
```


## Accessing frames

Access frames from the trace using standard array and iterator methods - frames are indexed in order from `0` just like the array returned by `debug_backtrace()`. Negative and out of range indexes return null:

```php
foreach ($trace as $frame) {
    echo $frame->location . ' - ' . $frame->function . PHP_EOL;
}

$frame = $trace[0]; // Get the first frame
echo (string)$frame; // Convert frame to string for a formatted output
```

## Licensing

Remnant is licensed under the MIT License. See [LICENSE](./LICENSE) for the full license text.
