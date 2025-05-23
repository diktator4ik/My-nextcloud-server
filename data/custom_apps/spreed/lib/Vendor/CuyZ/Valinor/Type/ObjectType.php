<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type;

/** @internal */
interface ObjectType extends Type
{
    /**
     * @return class-string
     */
    public function className(): string;
}
