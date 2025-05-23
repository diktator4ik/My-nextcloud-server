<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type\Types;

use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Message\ErrorMessage;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Message\MessageBuilder;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\BooleanType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Type;
use OCA\Talk\Vendor\CuyZ\Valinor\Utility\IsSingleton;

use function assert;
use function is_bool;

/** @internal */
final class NativeBooleanType implements BooleanType
{
    use IsSingleton;

    public function accepts(mixed $value): bool
    {
        return is_bool($value);
    }

    public function matches(Type $other): bool
    {
        if ($other instanceof UnionType) {
            return $other->isMatchedBy($this);
        }

        return $other instanceof self
            || $other instanceof MixedType;
    }

    public function canCast(mixed $value): bool
    {
        return is_bool($value)
            || $value === '1'
            || $value === '0'
            || $value === 1
            || $value === 0
            || $value === 'true'
            || $value === 'false';
    }

    public function cast(mixed $value): bool
    {
        assert($this->canCast($value));

        if ($value === 'false') {
            return false;
        }

        return (bool)$value;
    }

    public function errorMessage(): ErrorMessage
    {
        return MessageBuilder::newError('Value {source_value} is not a valid boolean.')->build();
    }

    public function toString(): string
    {
        return 'bool';
    }
}
