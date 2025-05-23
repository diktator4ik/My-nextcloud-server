<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Definition\Repository\Reflection\TypeResolver;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\Annotations;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Type;
use ReflectionProperty;

/** @internal */
final class PropertyTypeResolver
{
    public function __construct(private ReflectionTypeResolver $typeResolver) {}

    public function resolveTypeFor(ReflectionProperty $reflection): Type
    {
        $docBlockType = $this->extractTypeFromDocBlock($reflection);

        return $this->typeResolver->resolveType($reflection->getType(), $docBlockType);
    }

    public function resolveNativeTypeFor(ReflectionProperty $reflection): Type
    {
        return $this->typeResolver->resolveNativeType($reflection->getType());
    }

    public function extractTypeFromDocBlock(ReflectionProperty $reflection): ?string
    {
        $docBlock = $reflection->getDocComment();

        if ($docBlock === false) {
            return null;
        }

        return (new Annotations($docBlock))->firstOf(
            '@phpstan-var',
            '@psalm-var',
            '@var',
        )?->raw();
    }
}
