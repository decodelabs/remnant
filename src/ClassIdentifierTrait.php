<?php

/**
 * @package Remnant
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Remnant;

trait ClassIdentifierTrait
{
    public function jsonSerialize(): string
    {
        return $this->render();
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
