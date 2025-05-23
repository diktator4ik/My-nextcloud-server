<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\Scalar;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\InvalidType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\IntegerValueType;
use RuntimeException;

/** @internal */
final class IntegerRangeMissingMaxValue extends RuntimeException implements InvalidType
{
    public function __construct(IntegerValueType $min)
    {
        parent::__construct(
            "Missing max value for integer range, its signature must match `int<{$min->value()}, max>`.",
            1638788092
        );
    }
}
