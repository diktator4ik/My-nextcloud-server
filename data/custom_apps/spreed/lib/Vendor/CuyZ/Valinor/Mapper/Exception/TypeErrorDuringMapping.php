<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Exception;

use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Exception\UnresolvableShellType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Type;
use LogicException;

/** @internal */
final class TypeErrorDuringMapping extends LogicException
{
    public function __construct(Type $type, UnresolvableShellType $exception)
    {
        parent::__construct(
            "Error while trying to map to `{$type->toString()}`: {$exception->getMessage()}",
            1711526329,
            $exception,
        );
    }
}
