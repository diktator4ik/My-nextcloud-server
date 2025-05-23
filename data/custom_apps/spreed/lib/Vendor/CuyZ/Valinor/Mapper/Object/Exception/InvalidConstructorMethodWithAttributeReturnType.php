<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Object\Exception;

use OCA\Talk\Vendor\CuyZ\Valinor\Definition\MethodDefinition;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\UnresolvableType;
use LogicException;

/** @internal */
final class InvalidConstructorMethodWithAttributeReturnType extends LogicException
{
    /**
     * @param class-string $expectedClassName
     */
    public function __construct(string $expectedClassName, MethodDefinition $method)
    {
        if ($method->returnType instanceof UnresolvableType) {
            $message = $method->returnType->message();
        } else {
            $message = "Invalid return type `{$method->returnType->toString()}` for constructor `{$method->signature}`, it must be `$expectedClassName`.";
        }

        parent::__construct($message, 1708104783);
    }
}
