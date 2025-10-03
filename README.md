# Remnant

[![PHP from Packagist](https://img.shields.io/packagist/php-v/decodelabs/remnant?style=flat)](https://packagist.org/packages/decodelabs/remnant)
[![Latest Version](https://img.shields.io/packagist/v/decodelabs/remnant.svg?style=flat)](https://packagist.org/packages/decodelabs/remnant)
[![Total Downloads](https://img.shields.io/packagist/dt/decodelabs/remnant.svg?style=flat)](https://packagist.org/packages/decodelabs/remnant)
[![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/decodelabs/remnant/integrate.yml?branch=develop)](https://github.com/decodelabs/remnant/actions/workflows/integrate.yml)
[![PHPStan](https://img.shields.io/badge/PHPStan-enabled-44CC11.svg?longCache=true&style=flat)](https://github.com/phpstan/phpstan)
[![License](https://img.shields.io/packagist/l/decodelabs/remnant?style=flat)](https://packagist.org/packages/decodelabs/remnant)

### Easier stack traces

Remnant provides a simple way to generate more user-friendly stack traces in PHP.

---

## Installation

This package requires PHP 8.4 or higher.

Install via Composer:

```bash
composer require decodelabs/remnant
```

## Usage

Create a trace in the current context or from an Exception:

```php
use DecodeLabs\Remnant\Anchor\Rewind;
use DecodeLabs\Remnant\Anchor\FunctionIdentifier;
use DecodeLabs\Remnant\FunctionIdentifier\ObjectMethod;
use DecodeLabs\Remnant\Trace;

$trace = Trace::create();
$exceptionTrace = Trace::fromException($exception);

// Pass a rewind anchor to either method to rewind the trace by that many frames
$trace = Trace::create(new Rewind(2));
$exceptionTrace = Trace::fromException($exception, new Rewind(2));

// Pass a FunctionIdentifier anchor to rewind back to the last instance of the function
$trace = Trace::create(
    new FunctionIdentifier(
        new ObjectMethod(MyClass::class, 'myFunction')
    )
);
```

Access frames from the trace using standard array and iterator methods - frames are indexed in order from `0` just like the array returned by `debug_backtrace()`. Negative and out of range indexes return null:

```php
foreach ($trace as $frame) {
    echo $frame->location . ' - ' . $frame->function . PHP_EOL;
}

$frame = $trace[0]; // Get the first frame
echo (string)$frame; // Convert frame to string for a formatted output
```

### Rendering

Render a trace to a string - you can provide an optional `ViewOptions` object to customize the output.

Provide a list of `Filter` implementations to filter the frames before rendering.

Use the `ArgumentFormat` enum to control how arguments are rendered - `Count` (default), `InlineValues` or `NamedValues`.

```php
use DecodeLabs\Remnant\Filter\Paths as PathsFilter;
use DecodeLabs\Remnant\Filter\Vendor as VendorFilter;
use DecodeLabs\Remnant\Filter\FunctionIdentifier as FunctionIdentifierFilter;
use DecodeLabs\Remnant\Filter\ClassIdentifier as ClassIdentifierFilter;
use DecodeLabs\Remnant\FunctionIdentifier\ObjectMethod as ObjectMethodFunctionIdentifier;
use DecodeLabs\Remnant\ClassIdentifier\Native as NativeClassIdentifier;
use DecodeLabs\Remnant\ViewOptions;

$trace = Trace::create();

echo $trace->render(new ViewOptions(
    filters: [
        // Filter by paths
        new PathsFilter([
            '/path/to/filter/'
        ]),

        // Filter vendor dir
        new VendorFilter(),

        // Filter by function identifier
        new FunctionIdentifier(
            new ObjectMethod(MyClass::class, 'myFunction')
        ),

        // Filter by class identifier
        new ClassIdentifier(MyClass::class)
    ],
    argumentFormat: ArgumentFormat::NamedValues
));
```
`Paths` use prefix matching on canonicalised forward-slash paths; `Vendor` hides frames under `{projectRoot}/vendor`.

Paths are prettified where possible using [Monarch](https://github.com/decodelabs/monarch). Ensure `Monarch` is available in your project, and prepare your path aliases in your bootstrap:

```php
Monarch::getPaths()->alias('@components', '@run/src/@components');
```

```php
$trace = Trace::create();
echo $trace->render();
```

## Licensing

Remnant is licensed under the MIT License. See [LICENSE](./LICENSE) for the full license text.
