<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Definition\Repository\Cache\Compiler;

use OCA\Talk\Vendor\CuyZ\Valinor\Definition\Repository\Cache\Compiler\Exception\TypeCannotBeCompiled;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Type;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\ArrayKeyType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\ArrayType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\BooleanValueType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\CallableType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\ClassStringType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\NativeClassType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\FloatValueType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\IntegerRangeType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\IntegerValueType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\InterfaceType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\IntersectionType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\IterableType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\ListType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\MixedType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\NativeBooleanType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\EnumType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\NativeFloatType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\NativeIntegerType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\NativeStringType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\NegativeIntegerType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\NonEmptyArrayType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\NonEmptyListType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\NonEmptyStringType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\NonNegativeIntegerType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\NonPositiveIntegerType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\NullType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\NumericStringType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\PositiveIntegerType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\ShapedArrayElement;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\ShapedArrayType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\StringValueType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\UndefinedObjectType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\UnionType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\UnresolvableType;
use UnitEnum;

use function array_keys;
use function array_map;
use function implode;
use function var_export;

/** @internal */
final class TypeCompiler
{
    public function compile(Type $type): string
    {
        $class = $type::class;

        switch (true) {
            case $type instanceof NullType:
            case $type instanceof NativeBooleanType:
            case $type instanceof NativeFloatType:
            case $type instanceof NativeIntegerType:
            case $type instanceof PositiveIntegerType:
            case $type instanceof NegativeIntegerType:
            case $type instanceof NonPositiveIntegerType:
            case $type instanceof NonNegativeIntegerType:
            case $type instanceof NativeStringType:
            case $type instanceof NonEmptyStringType:
            case $type instanceof NumericStringType:
            case $type instanceof UndefinedObjectType:
            case $type instanceof CallableType:
            case $type instanceof MixedType:
                return "$class::get()";
            case $type instanceof BooleanValueType:
                return $type->value() === true
                    ? "$class::true()"
                    : "$class::false()";
            case $type instanceof IntegerRangeType:
                return "new $class({$type->min()}, {$type->max()})";
            case $type instanceof StringValueType:
                $value = var_export($type->toString(), true);

                return "$class::from($value)";
            case $type instanceof IntegerValueType:
            case $type instanceof FloatValueType:
                $value = var_export($type->value(), true);

                return "new $class($value)";
            case $type instanceof IntersectionType:
            case $type instanceof UnionType:
                $subTypes = array_map(
                    fn (Type $subType) => $this->compile($subType),
                    $type->types()
                );

                return "new $class(" . implode(', ', $subTypes) . ')';
            case $type instanceof ArrayKeyType:
                return match ($type->toString()) {
                    'string' => "$class::string()",
                    'int' => "$class::integer()",
                    default => "$class::default()",
                };
            case $type instanceof ShapedArrayType:
                $elements = implode(', ', array_map(
                    fn (ShapedArrayElement $element) => $this->compileArrayShapeElement($element),
                    $type->elements()
                ));

                if ($type->hasUnsealedType()) {
                    $unsealedType = $this->compile($type->unsealedType());

                    return "$class::unsealed($unsealedType, $elements)";
                } elseif ($type->isUnsealed()) {
                    return "$class::unsealedWithoutType($elements)";
                }

                return "new $class($elements)";
            case $type instanceof ArrayType:
            case $type instanceof NonEmptyArrayType:
                if ($type->toString() === 'array' || $type->toString() === 'non-empty-array') {
                    return "$class::native()";
                }

                $keyType = $this->compile($type->keyType());
                $subType = $this->compile($type->subType());

                return "new $class($keyType, $subType)";
            case $type instanceof ListType:
            case $type instanceof NonEmptyListType:
                if ($type->toString() === 'list' || $type->toString() === 'non-empty-list') {
                    return "$class::native()";
                }

                $subType = $this->compile($type->subType());

                return "new $class($subType)";
            case $type instanceof IterableType:
                $keyType = $this->compile($type->keyType());
                $subType = $this->compile($type->subType());

                return "new $class($keyType, $subType)";
            case $type instanceof NativeClassType:
            case $type instanceof InterfaceType:
                $generics = [];

                foreach ($type->generics() as $key => $generic) {
                    $generics[] = var_export($key, true) . ' => ' . $this->compile($generic);
                }

                $generics = implode(', ', $generics);

                return "new $class('{$type->className()}', [$generics])";
            case $type instanceof ClassStringType:
                if (null === $type->subType()) {
                    return "new $class()";
                }

                $subType = $this->compile($type->subType());

                return "new $class($subType)";
            case $type instanceof EnumType:
                $enumName = var_export($type->className(), true);
                $pattern = var_export($type->pattern(), true);

                $cases = array_map(
                    fn (string|int $key, UnitEnum $case) => var_export($key, true) . ' => ' . var_export($case, true),
                    array_keys($type->cases()),
                    $type->cases()
                );
                $cases = implode(', ', $cases);

                return "new $class($enumName, $pattern, [$cases])";
            case $type instanceof UnresolvableType:
                $raw = var_export($type->toString(), true);
                $message = var_export($type->message(), true);

                return "new $class($raw, $message)";
            default:
                throw new TypeCannotBeCompiled($type);
        }
    }

    private function compileArrayShapeElement(ShapedArrayElement $element): string
    {
        $class = ShapedArrayElement::class;
        $key = $this->compile($element->key());
        $type = $this->compile($element->type());
        $optional = var_export($element->isOptional(), true);

        return "new $class($key, $type, $optional)";
    }
}
