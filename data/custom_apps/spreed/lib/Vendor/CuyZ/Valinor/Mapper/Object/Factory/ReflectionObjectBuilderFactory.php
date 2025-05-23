<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Object\Factory;

use OCA\Talk\Vendor\CuyZ\Valinor\Definition\ClassDefinition;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Object\ReflectionObjectBuilder;

/** @internal */
final class ReflectionObjectBuilderFactory implements ObjectBuilderFactory
{
    public function for(ClassDefinition $class): array
    {
        return [new ReflectionObjectBuilder($class)];
    }
}
