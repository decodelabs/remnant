<?php

/**
 * @package Remnant
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Remnant;

use DecodeLabs\Monarch;
use JsonSerializable;
use Stringable;

class Location implements
    JsonSerializable,
    Stringable
{
    use PathPrettifyTrait;

    public readonly string $file;
    public readonly int $line;
    public readonly ?int $evalLine;

    public function __construct(
        string $file,
        int $line,
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

        if (class_exists(Monarch::class)) {
            $path = Monarch::getPaths()->prettify($path);
        }

        if (
            $path === $this->file &&
            $options->rootPath !== null &&
            !str_starts_with($path, $options->rootPath) &&
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

        while (true) {
            if (file_exists($path . '/composer.json')) {
                $name = basename($path);
                return '@pkg:' . $name . substr($originalPath, strlen($path));
            }

            if ($path === '/') {
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
        $output = $this->getPrettyFile($options) . ':' . $this->line;

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
    public function jsonSerialize(): array
    {
        $output = [
            'file' => $this->getPrettyFile(),
            'line' => $this->line,
        ];

        if ($this->evalLine !== null) {
            $output['evalLine'] = $this->evalLine;
        }

        return $output;
    }
}
