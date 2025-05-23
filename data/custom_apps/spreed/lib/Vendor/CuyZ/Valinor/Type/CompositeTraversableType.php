<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\ArrayKeyType;

/** @internal */
interface CompositeTraversableType extends CompositeType
{
    public function keyType(): ArrayKeyType;

    public function subType(): Type;
}
