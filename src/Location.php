<?php

/**
 * @package Remnant
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Remnant;

use DecodeLabs\Monarch;
use Stringable;

class Location implements
    JsonSerializableWithOptions,
    Stringable
{
    use JsonSerializableWithOptionsTrait;

    public readonly string $file;
    public readonly ?int $line;
    public readonly ?int $evalLine;

    public function __construct(
        string $file,
        ?int $line = null,
        ?int $evalLine = null
    ) {
        if (preg_match('/^(?<path>.+)\((?<line>[0-9]+)\) : eval\(\)\'d code/', $file, $matches)) {
            $file = $matches['path'];
            $evalLine = $line;
            $line = (int)$matches['line'];
        }

        $this->file = str_replace('\\', '/', $file);
        $this->line = $line;
        $this->evalLine = $evalLine;
    }

    public function equals(
        ?Location $location
    ): bool {
        if ($location === null) {
            return false;
        }

        return
            $location->file === $this->file &&
            $location->line === $this->line;
    }

    public function getPrettyFile(
        ?ViewOptions $options = null
    ): string {
        $options ??= new ViewOptions();
        $path = $this->file;

        if ($options->absolutePaths) {
            return $path;
        }

        if (class_exists(Monarch::class)) {
            $path = Monarch::getPaths()->prettify($path);
        }

        if (
            $path === $this->file &&
            $options->rootPath !== null &&
            !str_starts_with($path, $options->rootPath) &&
            !str_starts_with($path, '@') &&
            (null !== ($pkgPath = $this->getPackagePath($path)))
        ) {
            $path = $pkgPath;
        }

        return $path;
    }

    private function getPackagePath(
        string $path
    ): ?string {
        $originalPath = $path;
        $path = dirname($path);
        $max = 10;

        while ($max--) {
            if (file_exists($path . '/composer.json')) {
                $name = basename($path);
                return '@pkg:' . $name . substr($originalPath, strlen($path));
            }

            if (
                $path === '/' ||
                $path === '.'
            ) {
                break;
            }

            $path = dirname($path);
        }

        return null;
    }

    public function render(
        ?ViewOptions $options = null
    ): string {
        $options ??= new ViewOptions();
        $output = $this->getPrettyFile($options);

        if ($this->line !== null) {
            $output .= ':' . $this->line;
        }

        if ($this->evalLine !== null) {
            $output .= ' [eval:' . $this->evalLine . ']';
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
        $prettyOptions = clone $options;
        $prettyOptions->absolutePaths = false;

        $output = [
            'file' => $this->getPrettyFile($prettyOptions),
        ];

        if ($options->absolutePaths) {
            $output['absolute'] = $this->file;
        }

        $output['line'] = $this->line;

        if ($this->evalLine !== null) {
            $output['eval'] = $this->evalLine;
        }

        return $output;
    }
}
