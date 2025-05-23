<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\Iterable;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\InvalidType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Type;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\ArrayType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\NonEmptyArrayType;
use RuntimeException;

/** @internal */
final class ArrayCommaMissing extends RuntimeException implements InvalidType
{
    /**
     * @param class-string<ArrayType|NonEmptyArrayType> $arrayType
     */
    public function __construct(string $arrayType, Type $type)
    {
        $signature = "array<{$type->toString()}, ?>";

        if ($arrayType === NonEmptyArrayType::class) {
            $signature = "non-empty-array<{$type->toString()}, ?>";
        }

        parent::__construct(
            "A comma is missing for `$signature`.",
            1606483614
        );
    }
}
