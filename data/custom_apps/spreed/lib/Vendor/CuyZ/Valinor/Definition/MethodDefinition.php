<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Definition;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\Type;

/** @internal */
final class MethodDefinition
{
    public function __construct(
        /** @var non-empty-string */
        public readonly string $name,
        /** @var non-empty-string */
        public readonly string $signature,
        public readonly Attributes $attributes,
        public readonly Parameters $parameters,
        public readonly bool $isStatic,
        public readonly bool $isPublic,
        public readonly Type $returnType
    ) {}
}
