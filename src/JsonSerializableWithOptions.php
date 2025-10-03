<?php

/**
 * @package Remnant
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Remnant;

use JsonSerializable;

interface JsonSerializableWithOptions extends JsonSerializable
{
    public function jsonSerializeWithOptions(
        ?ViewOptions $options = null
    ): mixed;
}
