<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type\Types;

use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Message\ErrorMessage;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Message\MessageBuilder;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\StringType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Type;
use OCA\Talk\Vendor\CuyZ\Valinor\Utility\IsSingleton;
use Stringable;

use function assert;
use function is_numeric;
use function is_string;

/** @internal */
final class NativeStringType implements StringType
{
    use IsSingleton;

    public function accepts(mixed $value): bool
    {
        return is_string($value);
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
        return is_string($value)
            || is_numeric($value)
            || $value instanceof Stringable;
    }

    public function cast(mixed $value): string
    {
        assert($this->canCast($value));

        return (string)$value; // @phpstan-ignore-line
    }

    public function errorMessage(): ErrorMessage
    {
        return MessageBuilder::newError('Value {source_value} is not a valid string.')->build();
    }

    public function toString(): string
    {
        return 'string';
    }
}
