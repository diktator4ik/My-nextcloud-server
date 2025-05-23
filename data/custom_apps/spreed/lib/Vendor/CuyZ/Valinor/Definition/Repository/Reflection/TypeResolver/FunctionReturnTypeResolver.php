<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Definition\Repository\Reflection\TypeResolver;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\Annotations;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Type;
use ReflectionFunctionAbstract;

/** @internal */
final class FunctionReturnTypeResolver
{
    public function __construct(private ReflectionTypeResolver $typeResolver) {}

    public function resolveReturnTypeFor(ReflectionFunctionAbstract $reflection): Type
    {
        $docBlockType = $this->extractReturnTypeFromDocBlock($reflection);

        return $this->typeResolver->resolveType($reflection->getReturnType(), $docBlockType);
    }

    public function resolveNativeReturnTypeFor(ReflectionFunctionAbstract $reflection): Type
    {
        return $this->typeResolver->resolveNativeType($reflection->getReturnType());
    }

    private function extractReturnTypeFromDocBlock(ReflectionFunctionAbstract $reflection): ?string
    {
        $docBlock = $reflection->getDocComment();

        if ($docBlock === false) {
            return null;
        }

        return (new Annotations($docBlock))->firstOf(
            '@phpstan-return',
            '@psalm-return',
            '@return',
        )?->raw();
    }
}
