<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\Iterable;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\InvalidType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Type;
use RuntimeException;

/** @internal */
final class SimpleArrayClosingBracketMissing extends RuntimeException implements InvalidType
{
    public function __construct(Type $subType)
    {
        parent::__construct(
            "The closing bracket is missing for the array expression `{$subType->toString()}[]`.",
            1606474266
        );
    }
}
