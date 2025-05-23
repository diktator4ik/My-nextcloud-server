<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Definition\Repository;

use OCA\Talk\Vendor\CuyZ\Valinor\Definition\FunctionDefinition;

/** @internal */
interface FunctionDefinitionRepository
{
    public function for(callable $function): FunctionDefinition;
}
