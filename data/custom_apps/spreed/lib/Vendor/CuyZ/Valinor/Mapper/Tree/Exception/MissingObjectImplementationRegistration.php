<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Exception;

use OCA\Talk\Vendor\CuyZ\Valinor\Definition\FunctionDefinition;
use RuntimeException;

/** @internal */
final class MissingObjectImplementationRegistration extends RuntimeException
{
    public function __construct(string $name, FunctionDefinition $functionDefinition)
    {
        parent::__construct(
            "No implementation of `$name` found with return type `{$functionDefinition->returnType->toString()}` of `$functionDefinition->signature`.",
            1653990549
        );
    }
}
