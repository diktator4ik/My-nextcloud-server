<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Definition\Exception;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\Type;
use ReflectionClass;
use RuntimeException;

/** @internal */
final class InvalidExtendTagClassName extends RuntimeException
{
    /**
     * @param ReflectionClass<object> $reflection
     */
    public function __construct(ReflectionClass $reflection, Type $invalidExtendTag)
    {
        /** @var ReflectionClass<object> $parentClass */
        $parentClass = $reflection->getParentClass();

        parent::__construct(
            "The `@extends` tag of the class `$reflection->name` has invalid class `{$invalidExtendTag->toString()}`, it should be `$parentClass->name`.",
            1670183564,
        );
    }
}
