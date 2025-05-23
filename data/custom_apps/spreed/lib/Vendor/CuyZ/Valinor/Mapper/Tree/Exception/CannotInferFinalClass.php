<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Exception;

use OCA\Talk\Vendor\CuyZ\Valinor\Definition\FunctionDefinition;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\ClassType;
use RuntimeException;

/** @internal */
final class CannotInferFinalClass extends RuntimeException
{
    public function __construct(ClassType $class, FunctionDefinition $function)
    {
        parent::__construct(
            "Cannot infer final class `{$class->className()}` with function `$function->signature`.",
            1671468163
        );
    }
}
