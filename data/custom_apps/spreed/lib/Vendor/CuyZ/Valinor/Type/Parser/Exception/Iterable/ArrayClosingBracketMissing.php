<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\Iterable;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\InvalidType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\ArrayType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\NonEmptyArrayType;
use RuntimeException;

/** @internal */
final class ArrayClosingBracketMissing extends RuntimeException implements InvalidType
{
    public function __construct(ArrayType|NonEmptyArrayType $arrayType)
    {
        parent::__construct(
            "The closing bracket is missing for `{$arrayType->toString()}`.",
            1606483975
        );
    }
}
