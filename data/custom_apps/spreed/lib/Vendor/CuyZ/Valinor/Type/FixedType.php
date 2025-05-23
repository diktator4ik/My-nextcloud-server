<?php

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type;

/** @internal */
interface FixedType extends Type
{
    public function value(): bool|string|int|float;
}
