<?php

/**
 * @package Remnant
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Remnant\Filter;

use DecodeLabs\Remnant\Filter;
use DecodeLabs\Remnant\Frame;
use DecodeLabs\Remnant\FunctionIdentifier as FunctionIdentifierInterface;

class FunctionIdentifier implements Filter
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
        Frame $frame
    ): bool {
        return $frame->matches(...$this->identifiers);
    }
}
