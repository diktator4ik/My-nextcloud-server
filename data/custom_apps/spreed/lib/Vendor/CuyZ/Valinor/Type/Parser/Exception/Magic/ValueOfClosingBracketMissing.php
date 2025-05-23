<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\Magic;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\InvalidType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Type;
use RuntimeException;

/** @internal */
final class ValueOfClosingBracketMissing extends RuntimeException implements InvalidType
{
    public function __construct(Type $type)
    {
        parent::__construct(
            "The closing bracket is missing for `value-of<{$type->toString()}>`.",
            1717702289
        );
    }
}
