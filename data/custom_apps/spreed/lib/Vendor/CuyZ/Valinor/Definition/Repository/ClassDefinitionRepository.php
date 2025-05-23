<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Definition\Repository;

use OCA\Talk\Vendor\CuyZ\Valinor\Definition\ClassDefinition;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\ObjectType;

/** @internal */
interface ClassDefinitionRepository
{
    public function for(ObjectType $type): ClassDefinition;
}
