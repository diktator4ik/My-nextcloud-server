<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Definition;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\ObjectType;

/** @internal */
final class ClassDefinition
{
    public function __construct(
        /** @var class-string */
        public readonly string $name,
        public readonly ObjectType $type,
        public readonly Attributes $attributes,
        public readonly Properties $properties,
        public readonly Methods $methods,
        public readonly bool $isFinal,
        public readonly bool $isAbstract,
    ) {}
}
