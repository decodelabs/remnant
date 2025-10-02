<?php

/**
 * @package Remnant
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Remnant\Filter;

use DecodeLabs\Monarch;
use DecodeLabs\Remnant\Filter;
use DecodeLabs\Remnant\Frame;

class Paths implements Filter
{
    /**
     * @var list<string>
     */
    public array $paths = [];

    /**
     * @param list<string> $paths
     */
    public function __construct(
        array $paths
    ) {
        if (class_exists(Monarch::class)) {
            foreach ($paths as $path) {
                $this->paths[] = Monarch::getPaths()->resolve($path);
            }
        } else {
            $this->paths = $paths;
        }
    }



    public function accepts(
        Frame $frame
    ): bool {
        $file = $frame->callSite?->file;

        if ($file === null) {
            return true;
        }

        foreach ($this->paths as $path) {
            if (str_starts_with($file, $path)) {
                return false;
            }
        }

        return true;
    }
}
