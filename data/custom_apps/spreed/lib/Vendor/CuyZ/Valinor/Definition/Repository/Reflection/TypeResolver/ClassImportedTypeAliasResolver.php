<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Definition\Repository\Reflection\TypeResolver;

use OCA\Talk\Vendor\CuyZ\Valinor\Definition\Exception\InvalidTypeAliasImportClass;
use OCA\Talk\Vendor\CuyZ\Valinor\Definition\Exception\InvalidTypeAliasImportClassType;
use OCA\Talk\Vendor\CuyZ\Valinor\Definition\Exception\UnknownTypeAliasImport;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\ObjectType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\InvalidType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Factory\TypeParserFactory;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\Annotations;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Type;
use OCA\Talk\Vendor\CuyZ\Valinor\Utility\Reflection\Reflection;

use function current;
use function key;
use function next;

/** @internal */
final class ClassImportedTypeAliasResolver
{
    public function __construct(private TypeParserFactory $typeParserFactory) {}

    /**
     * @return array<string, Type>
     */
    public function resolveImportedTypeAliases(ObjectType $type): array
    {
        $importedTypesRaw = $this->extractImportedAliasesFromDocBlock($type->className());

        if ($importedTypesRaw === []) {
            return [];
        }

        $typeParser = $this->typeParserFactory->buildAdvancedTypeParserForClass($type);

        $importedTypes = [];

        foreach ($importedTypesRaw as $class => $types) {
            try {
                $classType = $typeParser->parse($class);
            } catch (InvalidType) {
                throw new InvalidTypeAliasImportClass($type, $class);
            }

            if (! $classType instanceof ObjectType) {
                throw new InvalidTypeAliasImportClassType($type, $classType);
            }

            $localTypes = (new ClassLocalTypeAliasResolver($this->typeParserFactory))->resolveLocalTypeAliases($classType);

            foreach ($types as $importedType) {
                if (! isset($localTypes[$importedType])) {
                    throw new UnknownTypeAliasImport($type, $classType->className(), $importedType);
                }

                $importedTypes[$importedType] = $localTypes[$importedType];
            }
        }

        return $importedTypes;
    }

    /**
     * @param class-string $className
     * @return array<non-empty-string, list<non-empty-string>>
     */
    private function extractImportedAliasesFromDocBlock(string $className): array
    {
        $docBlock = Reflection::class($className)->getDocComment();

        if ($docBlock === false) {
            return [];
        }

        $importedAliases = [];

        $annotations = (new Annotations($docBlock))->filteredByPriority(
            '@phpstan-import-type',
            '@psalm-import-type',
        );

        foreach ($annotations as $annotation) {
            $tokens = $annotation->filtered();

            $name = current($tokens);
            $from = next($tokens);

            if ($from !== 'from') {
                continue;
            }

            next($tokens);

            $key = key($tokens);

            // @phpstan-ignore identical.alwaysFalse (Somehow PHPStan does not properly infer the key)
            if ($key === null) {
                continue;
            }

            $class = $annotation->allAfter($key);

            $importedAliases[$class][] = $name;
        }

        return $importedAliases;
    }
}
