<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\Scalar;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\InvalidType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Type;
use RuntimeException;

/** @internal */
final class InvalidClassStringSubType extends RuntimeException implements InvalidType
{
    public function __construct(Type $type)
    {
        parent::__construct(
            "Invalid class string type `{$type->toString()}`, it must be a class name or an interface name.",
            1608034138
        );
    }
}
