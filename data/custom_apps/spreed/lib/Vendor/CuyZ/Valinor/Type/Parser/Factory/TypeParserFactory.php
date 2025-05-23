<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Factory;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\ObjectType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\TypeParser;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Type;
use ReflectionFunction;

/** @internal */
interface TypeParserFactory
{
    public function buildDefaultTypeParser(): TypeParser;

    /**
     * @param class-string $className
     */
    public function buildNativeTypeParserForClass(string $className): TypeParser;

    /**
     * @param array<string, Type> $aliases
     */
    public function buildAdvancedTypeParserForClass(ObjectType $type, array $aliases = []): TypeParser;

    public function buildNativeTypeParserForFunction(ReflectionFunction $reflection): TypeParser;

    public function buildAdvancedTypeParserForFunction(ReflectionFunction $reflection): TypeParser;
}
