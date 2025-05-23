<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\Enum;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\InvalidType;
use RuntimeException;
use UnitEnum;

/** @internal */
final class MissingSpecificEnumCase extends RuntimeException implements InvalidType
{
    /**
     * @param class-string<UnitEnum> $enumName
     */
    public function __construct(string $enumName)
    {
        parent::__construct(
            "Missing specific case for enum `$enumName::?` (cannot be `*`).",
            1653468438
        );
    }
}
