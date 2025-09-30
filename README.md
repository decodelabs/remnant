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
use DecodeLabs\Remnant\Trace;

$trace = Trace::create();
$exceptionTrace = Trace::fromException($exception);

// Pass an integer to either method to rewind the trace by that many frames
$trace = Trace::create(2);
$exceptionTrace = Trace::fromException($exception, 2);
```

Access frames from the trace using standard array methods:

```php
foreach ($trace as $frame) {
    echo $frame->file . ':' . $frame->line . ' - ' . $frame->function . PHP_EOL;
}

$frame = $trace[0]; // Get the first frame
echo (string)$frame; // Convert frame to string for a formatted output
```

## Licensing

Remnant is licensed under the MIT License. See [LICENSE](./LICENSE) for the full license text.
