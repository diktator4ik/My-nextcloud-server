<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\Factory;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\Type;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\BooleanValueType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\FloatValueType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\IntegerValueType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\EnumType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\ShapedArrayElement;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\ShapedArrayType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\StringValueType;
use UnitEnum;

use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function str_replace;

/** @internal */
final class ValueTypeFactory
{
    public static function from(mixed $value): Type
    {
        if (is_bool($value)) {
            return $value ? BooleanValueType::true() : BooleanValueType::false();
        }

        if (is_float($value)) {
            return new FloatValueType($value);
        }

        if (is_int($value)) {
            return new IntegerValueType($value);
        }

        if ($value instanceof UnitEnum) {
            return EnumType::fromPattern($value::class, $value->name);
        }

        if (is_string($value)) {
            if (str_contains($value, "'") && str_contains($value, '"')) {
                $value = "'" . str_replace("'", "\'", $value) . "'";
            } elseif (str_contains($value, "'")) {
                $value = '"' . $value . '"';
            } else {
                $value = "'" . $value . "'";
            }

            return StringValueType::from($value);
        }

        if (is_array($value)) {
            $elements = [];

            foreach ($value as $key => $child) {
                $keyType = is_string($key) ? new StringValueType($key) : new IntegerValueType($key);

                $elements[] = new ShapedArrayElement($keyType, self::from($child));
            }

            return new ShapedArrayType(...$elements);
        }

        throw new CannotBuildTypeFromValue($value);
    }
}
