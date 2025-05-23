<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Definition\Repository\Reflection\TypeResolver;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\ObjectType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\InvalidType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Factory\TypeParserFactory;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\Annotations;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Type;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\UnresolvableType;
use OCA\Talk\Vendor\CuyZ\Valinor\Utility\Reflection\Reflection;

use function current;
use function key;
use function next;

/** @internal */
final class ClassLocalTypeAliasResolver
{
    public function __construct(private TypeParserFactory $typeParserFactory) {}

    /**
     * @return array<string, Type>
     */
    public function resolveLocalTypeAliases(ObjectType $type): array
    {
        $localAliases = $this->extractLocalAliasesFromDocBlock($type->className());

        if ($localAliases === []) {
            return [];
        }

        $types = [];

        foreach ($localAliases as $name => $raw) {
            try {
                $typeParser = $this->typeParserFactory->buildAdvancedTypeParserForClass($type, $types);

                $types[$name] = $typeParser->parse($raw);
            } catch (InvalidType $exception) {
                $types[$name] = UnresolvableType::forLocalAlias($raw, $name, $type, $exception);
            }
        }

        return $types;
    }

    /**
     * @param class-string $className
     * @return array<non-empty-string, non-empty-string>
     */
    private function extractLocalAliasesFromDocBlock(string $className): array
    {
        $docBlock = Reflection::class($className)->getDocComment();

        if ($docBlock === false) {
            return [];
        }

        $aliases = [];

        $annotations = (new Annotations($docBlock))->filteredInOrder(
            '@phpstan-type',
            '@psalm-type',
        );

        foreach ($annotations as $annotation) {
            $tokens = $annotation->filtered();

            $name = current($tokens);
            $next = next($tokens);

            if ($next === '=') {
                next($tokens);
            }

            $key = key($tokens);

            // @phpstan-ignore notIdentical.alwaysTrue (Somehow PHPStan does not properly infer the key)
            if ($key !== null) {
                $aliases[$name] = $annotation->allAfter($key);
            }
        }

        return $aliases;
    }
}
