<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Object\Exception;

use OCA\Talk\Vendor\CuyZ\Valinor\Definition\FunctionDefinition;
use LogicException;

/** @internal */
final class MissingConstructorClassTypeParameter extends LogicException
{
    public function __construct(FunctionDefinition $function)
    {
        parent::__construct(
            "Missing first parameter of type `class-string` for the constructor `{$function->signature}`.",
            1661516853
        );
    }
}
