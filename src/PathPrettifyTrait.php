<?php

/**
 * @package Remnant
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Remnant;

use DecodeLabs\Monarch;

trait PathPrettifyTrait
{
    private static function prettifyPath(
        string $path
    ): string {
        if (class_exists(Monarch::class)) {
            return Monarch::$paths->prettify($path);
        }

        return $path;
    }
}
