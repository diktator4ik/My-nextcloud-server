<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\Scalar;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\InvalidType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\IntegerValueType;
use RuntimeException;

/** @internal */
final class IntegerRangeMissingComma extends RuntimeException implements InvalidType
{
    public function __construct(IntegerValueType $min)
    {
        parent::__construct(
            "Missing comma in integer range signature `int<{$min->value()}, ?>`.",
            1638787915
        );
    }
}
