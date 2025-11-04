<?php

/**
 * Remnant
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Remnant\Filter;

use DecodeLabs\Remnant\ClassIdentifier as ClassIdentifierInterface;
use DecodeLabs\Remnant\Filter;
use DecodeLabs\Remnant\Frame;

class ClassIdentifier implements Filter
{
    /**
     * @var array<ClassIdentifierInterface>
     */
    public readonly array $identifiers;

    public function __construct(
        string|ClassIdentifierInterface ...$identifiers
    ) {
        $this->identifiers = array_map(function ($identifier) {
            if (is_string($identifier)) {
                return Frame::createClassIdentifier($identifier);
            }

            return $identifier;
        }, $identifiers);
    }

    public function accepts(
        Frame $frame
    ): bool {
        return $frame->matches(...$this->identifiers);
    }
}
