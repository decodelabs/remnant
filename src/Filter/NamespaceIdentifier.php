<?php

/**
 * @package Remnant
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Remnant\Filter;

use DecodeLabs\Remnant\Filter;
use DecodeLabs\Remnant\Frame;
use DecodeLabs\Remnant\FunctionIdentifier\NamespaceFunction;
use DecodeLabs\Remnant\FunctionIdentifier\ObjectMethod as ObjectMethodFunction;
use DecodeLabs\Remnant\FunctionIdentifier\StaticMethod as StaticMethodFunction;

class NamespaceIdentifier implements Filter
{
    /**
     * @var array<string>
     */
    public readonly array $identifiers;

    public function __construct(
        string ...$identifiers
    ) {
        $this->identifiers = $identifiers;
    }

    public function accepts(
        Frame $frame
    ): bool {
        if (
            (
                !$frame->function instanceof NamespaceFunction &&
                !$frame->function instanceof ObjectMethodFunction &&
                !$frame->function instanceof StaticMethodFunction
            ) ||
            $frame->function->namespace === null
        ) {
            return true;
        }

        foreach ($this->identifiers as $identifier) {
            if (
                $frame->function->namespace === $identifier ||
                str_starts_with($frame->function->namespace, $identifier . '\\')
            ) {
                return false;
            }
        }

        return true;
    }
}
