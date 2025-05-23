<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type\Types;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\Type;
use OCA\Talk\Vendor\CuyZ\Valinor\Utility\IsSingleton;

/** @internal */
final class MixedType implements Type
{
    use IsSingleton;

    public function accepts(mixed $value): bool
    {
        return true;
    }

    public function matches(Type $other): bool
    {
        return $other instanceof self;
    }

    public function toString(): string
    {
        return 'mixed';
    }
}
