<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Object\Exception;

use OCA\Talk\Vendor\CuyZ\Valinor\Definition\ClassDefinition;
use RuntimeException;

/** @internal */
final class CannotInstantiateObject extends RuntimeException
{
    public function __construct(ClassDefinition $class)
    {
        parent::__construct(
            "No available constructor found for class `{$class->name}`.",
            1646916477
        );
    }
}
