<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Source;

/** @api */
interface IdentifiableSource
{
    public function sourceName(): string;
}
