<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\Iterable;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\InvalidType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\ShapedArrayElement;
use RuntimeException;

use function implode;

/** @internal */
final class ShapedArrayCommaMissing extends RuntimeException implements InvalidType
{
    /**
     * @param ShapedArrayElement[] $elements
     */
    public function __construct(array $elements)
    {
        $signature = 'array{' . implode(', ', array_map(fn (ShapedArrayElement $element) => $element->toString(), $elements));

        parent::__construct(
            "Comma missing in shaped array signature `$signature`.",
            1631286589
        );
    }
}
