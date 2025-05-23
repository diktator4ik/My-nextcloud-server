<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Exception;

use OCA\Talk\Vendor\CuyZ\Valinor\Definition\FunctionDefinition;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Exception\UnresolvableShellType;
use LogicException;

/** @internal */
final class TypeErrorDuringArgumentsMapping extends LogicException
{
    public function __construct(FunctionDefinition $function, UnresolvableShellType $exception)
    {
        parent::__construct(
            "Could not map arguments of `$function->signature`: {$exception->getMessage()}",
            1711534351,
            $exception,
        );
    }
}
