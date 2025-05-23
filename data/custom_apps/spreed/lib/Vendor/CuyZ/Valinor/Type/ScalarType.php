<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type;

use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Message\ErrorMessage;

/** @internal */
interface ScalarType extends Type
{
    public function canCast(mixed $value): bool;

    public function cast(mixed $value): bool|string|int|float;

    public function errorMessage(): ErrorMessage;
}
