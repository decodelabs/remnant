<?php

/**
 * @package Remnant
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Remnant;

enum ArgumentFormat
{
    case Count;
    case InlineValues;
    case NamedValues;
}
