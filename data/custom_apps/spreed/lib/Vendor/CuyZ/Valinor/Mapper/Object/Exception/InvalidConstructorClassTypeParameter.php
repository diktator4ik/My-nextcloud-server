<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Object\Exception;

use OCA\Talk\Vendor\CuyZ\Valinor\Definition\FunctionDefinition;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Type;
use LogicException;

/** @internal */
final class InvalidConstructorClassTypeParameter extends LogicException
{
    public function __construct(FunctionDefinition $function, Type $type)
    {
        parent::__construct(
            "Invalid type `{$type->toString()}` for the first parameter of the constructor `{$function->signature}`, it should be of type `class-string`.",
            1661517000
        );
    }
}
