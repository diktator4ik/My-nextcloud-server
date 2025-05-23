<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Object\Exception;

use OCA\Talk\Vendor\CuyZ\Valinor\Definition\FunctionDefinition;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\UnresolvableType;
use LogicException;

/** @internal */
final class InvalidConstructorReturnType extends LogicException
{
    public function __construct(FunctionDefinition $function)
    {
        if ($function->returnType instanceof UnresolvableType) {
            $message = $function->returnType->message();
        } else {
            $message = "Invalid return type `{$function->returnType->toString()}` for constructor `{$function->signature}`, it must be a valid class name.";
        }

        parent::__construct($message, 1659446121);
    }
}
