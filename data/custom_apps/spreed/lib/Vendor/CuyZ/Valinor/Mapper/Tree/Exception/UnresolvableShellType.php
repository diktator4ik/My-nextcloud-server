<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Exception;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\UnresolvableType;
use LogicException;

/** @internal */
final class UnresolvableShellType extends LogicException
{
    public function __construct(UnresolvableType $type)
    {
        parent::__construct($type->message());
    }
}
