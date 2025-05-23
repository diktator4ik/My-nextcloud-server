<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Definition\Repository;

use OCA\Talk\Vendor\CuyZ\Valinor\Definition\AttributeDefinition;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use Reflector;

/** @internal */
interface AttributesRepository
{
    /**
     * @param ReflectionClass<object>|ReflectionProperty|ReflectionMethod|ReflectionFunction|ReflectionParameter $reflection
     * @return list<AttributeDefinition>
     */
    public function for(Reflector $reflection): array;
}
