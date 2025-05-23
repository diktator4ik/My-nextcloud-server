<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\Type;
use RuntimeException;

/** @internal */
final class RightIntersectionTypeMissing extends RuntimeException implements InvalidType
{
    public function __construct(Type $type)
    {
        parent::__construct(
            "Right type is missing for intersection `{$type->toString()}&?`.",
            1631612575
        );
    }
}
