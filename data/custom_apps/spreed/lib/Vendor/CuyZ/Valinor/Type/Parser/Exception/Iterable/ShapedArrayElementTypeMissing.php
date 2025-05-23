<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\Iterable;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\InvalidType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\IntegerValueType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\ShapedArrayElement;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\StringValueType;
use RuntimeException;

use function implode;

/** @internal */
final class ShapedArrayElementTypeMissing extends RuntimeException implements InvalidType
{
    /**
     * @param ShapedArrayElement[] $elements
     */
    public function __construct(array $elements, StringValueType|IntegerValueType $key, bool $optional)
    {
        $signature = 'array{' . implode(', ', array_map(fn (ShapedArrayElement $element) => $element->toString(), $elements));

        if (! empty($elements)) {
            $signature .= ', ';
        }

        $signature .= $key->value();

        if ($optional) {
            $signature .= '?';
        }

        $signature .= ':';

        parent::__construct(
            "Missing element type in shaped array signature `$signature`.",
            1631286250
        );
    }
}
