<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Normalizer\Exception;

use OCA\Talk\Vendor\CuyZ\Valinor\Definition\MethodDefinition;
use LogicException;

/** @internal */
final class KeyTransformerHasTooManyParameters extends LogicException
{
    public function __construct(MethodDefinition $method)
    {
        parent::__construct(
            "Key transformer must have at most 1 parameter, {$method->parameters->count()} given for `$method->signature`.",
            1701701102,
        );
    }
}
