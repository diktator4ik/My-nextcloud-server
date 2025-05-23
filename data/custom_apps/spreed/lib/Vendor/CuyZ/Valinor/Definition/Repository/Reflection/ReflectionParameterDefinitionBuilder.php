<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Definition\Repository\Reflection;

use OCA\Talk\Vendor\CuyZ\Valinor\Definition\Attributes;
use OCA\Talk\Vendor\CuyZ\Valinor\Definition\ParameterDefinition;
use OCA\Talk\Vendor\CuyZ\Valinor\Definition\Repository\AttributesRepository;
use OCA\Talk\Vendor\CuyZ\Valinor\Definition\Repository\Reflection\TypeResolver\ParameterTypeResolver;
use OCA\Talk\Vendor\CuyZ\Valinor\Definition\Repository\Reflection\TypeResolver\ReflectionTypeResolver;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\UnresolvableType;
use ReflectionParameter;

/** @internal */
final class ReflectionParameterDefinitionBuilder
{
    public function __construct(private AttributesRepository $attributesRepository) {}

    public function for(ReflectionParameter $reflection, ReflectionTypeResolver $typeResolver): ParameterDefinition
    {
        $parameterTypeResolver = new ParameterTypeResolver($typeResolver);

        /** @var non-empty-string $name */
        $name = $reflection->name;
        $signature = $this->signature($reflection);
        $type = $parameterTypeResolver->resolveTypeFor($reflection);
        $nativeType = $parameterTypeResolver->resolveNativeTypeFor($reflection);
        $isOptional = $reflection->isOptional();
        $isVariadic = $reflection->isVariadic();

        if ($reflection->isDefaultValueAvailable()) {
            $defaultValue = $reflection->getDefaultValue();
        } elseif ($reflection->isVariadic()) {
            $defaultValue = [];
        } else {
            $defaultValue = null;
        }

        if ($type instanceof UnresolvableType) {
            $type = $type->forParameter($signature);
        } elseif (! $type->matches($nativeType)) {
            $type = UnresolvableType::forNonMatchingParameterTypes($signature, $nativeType, $type);
        } elseif ($isOptional && ! $type->accepts($defaultValue)) {
            $type = UnresolvableType::forInvalidParameterDefaultValue($signature, $type, $defaultValue);
        }

        return new ParameterDefinition(
            $name,
            $signature,
            $type,
            $nativeType,
            $isOptional,
            $isVariadic,
            $defaultValue,
            new Attributes(...$this->attributesRepository->for($reflection)),
        );
    }

    /**
     * @return non-empty-string
     */
    private function signature(ReflectionParameter $reflection): string
    {
        $signature = $reflection->getDeclaringFunction()->name . "(\$$reflection->name)";
        $class = $reflection->getDeclaringClass();

        if ($class) {
            $signature = $class->name . '::' . $signature;
        }

        return $signature;
    }
}
