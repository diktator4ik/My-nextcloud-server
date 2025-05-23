<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\Iterable;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\InvalidType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Type;
use RuntimeException;

/** @internal */
final class IterableClosingBracketMissing extends RuntimeException implements InvalidType
{
    public function __construct(Type $keyType, Type $subtype)
    {
        parent::__construct(
            "The closing bracket is missing for `iterable<{$keyType->toString()}, {$subtype->toString()}>`.",
            1618994728
        );
    }
}
