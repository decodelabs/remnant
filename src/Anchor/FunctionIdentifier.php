<?php

/**
 * @package Remnant
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Remnant\Anchor;

use DecodeLabs\Remnant\Anchor;
use DecodeLabs\Remnant\Frame;
use DecodeLabs\Remnant\FunctionIdentifier as FunctionIdentifierInterface;

class FunctionIdentifier implements Anchor
{
    /**
     * @var array<FunctionIdentifierInterface>
     */
    public readonly array $identifiers;

    public function __construct(
        FunctionIdentifierInterface ...$identifiers
    ) {
        $this->identifiers = $identifiers;
    }

    public function accepts(
        int $offset,
        Frame $frame
    ): bool {
        foreach ($this->identifiers as $identifier) {
            if ($frame->function->equals($identifier)) {
                return true;
            }
        }

        return false;
    }
}
