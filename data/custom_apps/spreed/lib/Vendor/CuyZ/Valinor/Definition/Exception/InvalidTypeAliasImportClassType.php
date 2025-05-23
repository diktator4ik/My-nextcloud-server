<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Definition\Exception;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\ObjectType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Type;
use LogicException;

/** @internal */
final class InvalidTypeAliasImportClassType extends LogicException
{
    public function __construct(ObjectType $classType, Type $type)
    {
        parent::__construct(
            "Importing a type alias can only be done with classes, `{$type->toString()}` was given in class `{$classType->className()}`.",
            1638535608
        );
    }
}
