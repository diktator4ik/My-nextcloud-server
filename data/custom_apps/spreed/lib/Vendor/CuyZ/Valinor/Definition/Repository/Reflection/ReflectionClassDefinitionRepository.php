<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Definition\Repository\Reflection;

use OCA\Talk\Vendor\CuyZ\Valinor\Definition\Attributes;
use OCA\Talk\Vendor\CuyZ\Valinor\Definition\ClassDefinition;
use OCA\Talk\Vendor\CuyZ\Valinor\Definition\Exception\ClassTypeAliasesDuplication;
use OCA\Talk\Vendor\CuyZ\Valinor\Definition\MethodDefinition;
use OCA\Talk\Vendor\CuyZ\Valinor\Definition\Methods;
use OCA\Talk\Vendor\CuyZ\Valinor\Definition\Properties;
use OCA\Talk\Vendor\CuyZ\Valinor\Definition\PropertyDefinition;
use OCA\Talk\Vendor\CuyZ\Valinor\Definition\Repository\AttributesRepository;
use OCA\Talk\Vendor\CuyZ\Valinor\Definition\Repository\ClassDefinitionRepository;
use OCA\Talk\Vendor\CuyZ\Valinor\Definition\Repository\Reflection\TypeResolver\ClassImportedTypeAliasResolver;
use OCA\Talk\Vendor\CuyZ\Valinor\Definition\Repository\Reflection\TypeResolver\ClassLocalTypeAliasResolver;
use OCA\Talk\Vendor\CuyZ\Valinor\Definition\Repository\Reflection\TypeResolver\ClassParentTypeResolver;
use OCA\Talk\Vendor\CuyZ\Valinor\Definition\Repository\Reflection\TypeResolver\ReflectionTypeResolver;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\GenericType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\ObjectType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Factory\TypeParserFactory;
use OCA\Talk\Vendor\CuyZ\Valinor\Utility\Reflection\Reflection;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

use function array_filter;
use function array_keys;
use function array_map;

/** @internal */
final class ReflectionClassDefinitionRepository implements ClassDefinitionRepository
{
    private TypeParserFactory $typeParserFactory;

    private AttributesRepository $attributesRepository;

    private ReflectionPropertyDefinitionBuilder $propertyBuilder;

    private ReflectionMethodDefinitionBuilder $methodBuilder;

    /** @var array<string, ReflectionTypeResolver> */
    private array $typeResolver = [];

    /**
     * @param list<class-string> $allowedAttributes
     */
    public function __construct(
        TypeParserFactory $typeParserFactory,
        array $allowedAttributes,
    ) {
        $this->typeParserFactory = $typeParserFactory;
        $this->attributesRepository = new ReflectionAttributesRepository($this, $allowedAttributes);
        $this->propertyBuilder = new ReflectionPropertyDefinitionBuilder($this->attributesRepository);
        $this->methodBuilder = new ReflectionMethodDefinitionBuilder($this->attributesRepository);
    }

    public function for(ObjectType $type): ClassDefinition
    {
        $reflection = Reflection::class($type->className());

        return new ClassDefinition(
            $reflection->name,
            $type,
            new Attributes(...$this->attributesRepository->for($reflection)),
            new Properties(...$this->properties($type)),
            new Methods(...$this->methods($type)),
            $reflection->isFinal(),
            $reflection->isAbstract(),
        );
    }

    /**
     * @return list<PropertyDefinition>
     */
    private function properties(ObjectType $type): array
    {
        return array_map(
            function (ReflectionProperty $property) use ($type) {
                $typeResolver = $this->typeResolver($type, $property->getDeclaringClass());

                return $this->propertyBuilder->for($property, $typeResolver);
            },
            Reflection::class($type->className())->getProperties(),
        );
    }

    /**
     * @return list<MethodDefinition>
     */
    private function methods(ObjectType $type): array
    {
        $reflection = Reflection::class($type->className());
        $methods = $reflection->getMethods();

        // Because `ReflectionMethod::getMethods()` wont list the constructor if
        // it comes from a parent class AND is not public, we need to manually
        // fetch it and add it to the list.
        if ($reflection->hasMethod('__construct')) {
            $methods[] = $reflection->getMethod('__construct');
        }

        return array_map(function (ReflectionMethod $method) use ($type) {
            $typeResolver = $this->typeResolver($type, $method->getDeclaringClass());

            return $this->methodBuilder->for($method, $typeResolver);
        }, $methods);
    }

    /**
     * @param ReflectionClass<object> $target
     */
    private function typeResolver(ObjectType $type, ReflectionClass $target): ReflectionTypeResolver
    {
        $typeKey = $target->isInterface()
            ? "{$type->toString()}/{$type->className()}"
            : "{$type->toString()}/$target->name";

        if (isset($this->typeResolver[$typeKey])) {
            return $this->typeResolver[$typeKey];
        }

        $parentTypeResolver = new ClassParentTypeResolver($this->typeParserFactory);

        while ($type->className() !== $target->name) {
            $type = $parentTypeResolver->resolveParentTypeFor($type);
        }

        $localTypeAliasResolver = new ClassLocalTypeAliasResolver($this->typeParserFactory);
        $importedTypeAliasResolver = new ClassImportedTypeAliasResolver($this->typeParserFactory);

        $generics = $type instanceof GenericType ? $type->generics() : [];
        $localAliases = $localTypeAliasResolver->resolveLocalTypeAliases($type);
        $importedAliases = $importedTypeAliasResolver->resolveImportedTypeAliases($type);

        $duplicates = [];
        $keys = [...array_keys($generics), ...array_keys($localAliases), ...array_keys($importedAliases)];

        foreach ($keys as $key) {
            $sameKeys = array_filter($keys, fn ($value) => $value === $key);

            if (count($sameKeys) > 1) {
                $duplicates[$key] = null;
            }
        }

        if (count($duplicates) > 0) {
            throw new ClassTypeAliasesDuplication($type->className(), ...array_keys($duplicates));
        }

        $advancedParser = $this->typeParserFactory->buildAdvancedTypeParserForClass($type, $generics + $localAliases + $importedAliases);
        $nativeParser = $this->typeParserFactory->buildNativeTypeParserForClass($type->className());

        return $this->typeResolver[$typeKey] = new ReflectionTypeResolver($nativeParser, $advancedParser);
    }
}
