<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\Constant;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\InvalidType;
use RuntimeException;

/** @internal */
final class MissingClassConstantCase extends RuntimeException implements InvalidType
{
    /**
     * @param class-string $className
     */
    public function __construct(string $className)
    {
        parent::__construct(
            "Missing case name for class constant `$className::?`.",
            1664905018
        );
    }
}
