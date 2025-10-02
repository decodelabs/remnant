<?php

/**
 * @package Remnant
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Remnant\Filter;

use DecodeLabs\Remnant\ClassIdentifier as ClassIdentifierInterface;
use DecodeLabs\Remnant\Filter;
use DecodeLabs\Remnant\Frame;
use DecodeLabs\Remnant\FunctionIdentifier\ObjectMethod as ObjectMethodFunctionIdentifier;
use DecodeLabs\Remnant\FunctionIdentifier\StaticMethod as StaticMethodFunctionIdentifier;

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
        if (
            !$frame->function instanceof ObjectMethodFunctionIdentifier &&
            !$frame->function instanceof StaticMethodFunctionIdentifier
        ) {
            return false;
        }

        foreach ($this->identifiers as $identifier) {
            if ($frame->function->class->equals($identifier)) {
                return true;
            }
        }

        return false;
    }
}
