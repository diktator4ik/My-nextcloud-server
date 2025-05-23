<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\Iterable;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\InvalidType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\ListType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\NonEmptyListType;
use RuntimeException;

/** @internal */
final class ListClosingBracketMissing extends RuntimeException implements InvalidType
{
    public function __construct(ListType|NonEmptyListType $listType)
    {
        parent::__construct(
            "The closing bracket is missing for `{$listType->toString()}`.",
            1634035071
        );
    }
}
