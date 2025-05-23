<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\Scalar;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\InvalidType;
use RuntimeException;

/** @internal */
final class ReversedValuesForIntegerRange extends RuntimeException implements InvalidType
{
    public function __construct(int $min, int $max)
    {
        parent::__construct(
            "The min value must be less than the max for integer range `int<$min, $max>`.",
            1638787061
        );
    }
}
