<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\Scalar;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\InvalidType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\IntegerValueType;
use RuntimeException;

/** @internal */
final class IntegerRangeMissingClosingBracket extends RuntimeException implements InvalidType
{
    public function __construct(IntegerValueType $min, IntegerValueType $max)
    {
        parent::__construct(
            "Missing closing bracket in integer range signature `int<{$min->value()}, {$max->value()}>`.",
            1638788306
        );
    }
}
