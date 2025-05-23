<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\Iterable;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\InvalidType;
use RuntimeException;

/** @internal */
final class ShapedArrayElementDuplicatedKey extends RuntimeException implements InvalidType
{
    public function __construct(string $key, string $signature)
    {
        parent::__construct(
            "Key `$key` cannot be used several times in shaped array signature `$signature`.",
            1631283279
        );
    }
}
