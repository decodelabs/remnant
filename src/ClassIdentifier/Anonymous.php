<?php

/**
 * @package Remnant
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Remnant\ClassIdentifier;

use DecodeLabs\Remnant\ClassIdentifier;
use DecodeLabs\Remnant\ClassIdentifierTrait;
use DecodeLabs\Remnant\Location;
use DecodeLabs\Remnant\ViewOptions;
use ReflectionClass;

class Anonymous implements ClassIdentifier
{
    use ClassIdentifierTrait;

    public string $name {
        get => '{anonymous}';
    }

    public readonly ?string $id;
    public readonly ?Location $location;

    /**
     * @var ?ReflectionClass<object>
     */
    public readonly ?ReflectionClass $reflection;

    public static function fromClassString(
        string $class,
        ?object $object = null
    ): self {
        if (preg_match('/^class@anonymous(?<file>.+)\:(?<line>[0-9]+)(?<id>(0x|\$)[0-9a-f]+)$/', $class, $matches)) {
            return new self(
                location: new Location(
                    file: trim($matches['file']),
                    line: (int)$matches['line']
                ),
                id: $matches['id'],
                object: $object
            );
        }

        return new self(
            location: null,
            id: null,
            object: $object
        );
    }

    public function __construct(
        ?Location $location,
        ?string $id = null,
        ?object $object = null
    ) {
        $this->location = $location;
        $this->id = $id;

        if ($object !== null) {
            $this->reflection = new ReflectionClass($object);
        } else {
            $this->reflection = null;
        }
    }

    public function equals(
        ClassIdentifier $identifier
    ): bool {
        return
            $identifier instanceof self &&
            $identifier->location?->equals($this->location);
    }

    public function render(
        ?ViewOptions $options = null
    ): string {
        if ($this->location === null) {
            return '{anonymous}';
        }

        return '{anonymous:' . $this->location->getPrettyFile() . ':' . $this->location->line . '}';
    }
}
