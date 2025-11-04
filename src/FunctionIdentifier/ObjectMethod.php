<?php

/**
 * Remnant
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Remnant\FunctionIdentifier;

use DecodeLabs\Remnant\FunctionIdentifier;

class ObjectMethod implements FunctionIdentifier
{
    use ClassTrait;

    public string $separator {
        get => '->';
    }
}
