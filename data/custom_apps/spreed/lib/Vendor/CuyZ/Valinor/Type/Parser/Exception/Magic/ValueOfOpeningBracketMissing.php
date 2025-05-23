<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\Magic;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\InvalidType;
use RuntimeException;

/** @internal */
final class ValueOfOpeningBracketMissing extends RuntimeException implements InvalidType
{
    public function __construct()
    {
        parent::__construct(
            "The opening bracket is missing for `value-of<...>`.",
            1717702268
        );
    }
}
