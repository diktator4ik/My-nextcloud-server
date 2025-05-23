<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\Scalar;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\InvalidType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Type;
use RuntimeException;

/** @internal */
final class IntegerRangeInvalidMinValue extends RuntimeException implements InvalidType
{
    public function __construct(Type $type)
    {
        parent::__construct(
            "Invalid type `{$type->toString()}` for min value of integer range, it must be either `min` or an integer value.",
            1638787807
        );
    }
}
