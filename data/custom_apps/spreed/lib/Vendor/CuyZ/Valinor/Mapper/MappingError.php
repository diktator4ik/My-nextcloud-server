<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Mapper;

use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Node;
use Throwable;

/** @api */
interface MappingError extends Throwable
{
    public function node(): Node;
}
