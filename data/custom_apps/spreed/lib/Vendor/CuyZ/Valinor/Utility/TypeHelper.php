<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Utility;

use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Object\Argument;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Object\Arguments;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\BooleanType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\CompositeType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\FixedType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\FloatType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\IntegerType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\ObjectType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\StringType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Type;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\EnumType;

/** @internal */
final class TypeHelper
{
    /**
     * Sorting the scalar types by priority: int, float, string, bool.
     */
    public static function typePriority(Type $type): int
    {
        return match (true) {
            $type instanceof IntegerType => 4,
            $type instanceof FloatType => 3,
            $type instanceof StringType => 2,
            $type instanceof BooleanType => 1,
            default => 0,
        };
    }

    public static function dump(Type $type, bool $surround = true): string
    {
        if ($type instanceof EnumType) {
            $text = $type->readableSignature();
        } elseif ($type instanceof FixedType) {
            return $type->toString();
        } elseif (self::containsObject($type)) {
            $text = '?';
        } else {
            $text = $type->toString();
        }

        return $surround ? "`$text`" : $text;
    }

    public static function dumpArguments(Arguments $arguments): string
    {
        if (count($arguments) === 0) {
            return 'array';
        }

        if (count($arguments) === 1) {
            return self::dump($arguments->at(0)->type());
        }

        $parameters = array_map(
            function (Argument $argument) {
                $name = $argument->name();
                $type = $argument->type();

                $signature = self::dump($type, false);

                return $argument->isRequired() ? "$name: $signature" : "$name?: $signature";
            },
            [...$arguments],
        );

        return '`array{' . implode(', ', $parameters) . '}`';
    }

    public static function containsObject(Type $type): bool
    {
        if ($type instanceof CompositeType) {
            foreach ($type->traverse() as $subType) {
                if (self::containsObject($subType)) {
                    return true;
                }
            }
        }

        return $type instanceof ObjectType;
    }
}
