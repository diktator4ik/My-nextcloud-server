<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\Exception;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\InvalidType;
use LogicException;

/** @internal */
final class ForbiddenMixedType extends LogicException implements InvalidType
{
    public function __construct()
    {
        parent::__construct(
            "Type `mixed` can only be used as a standalone type and not as a union member.",
            1608146262
        );
    }
}
