<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type;

/** @internal */
interface IntegerType extends ScalarType
{
    public function cast(mixed $value): int;
}
