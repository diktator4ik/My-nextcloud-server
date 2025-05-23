<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Exception;

use OCA\Talk\Vendor\CuyZ\Valinor\Utility\ValueDumper;
use RuntimeException;

/** @internal */
final class InvalidResolvedImplementationValue extends RuntimeException
{
    public function __construct(string $name, mixed $value)
    {
        $value = ValueDumper::dump($value);

        parent::__construct(
            "Invalid value $value, expected a subtype of `$name`.",
            1630091260
        );
    }
}
