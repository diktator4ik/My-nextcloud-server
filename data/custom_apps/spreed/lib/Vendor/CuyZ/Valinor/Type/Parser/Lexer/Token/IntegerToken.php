<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\Token;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\Scalar\IntegerRangeInvalidMaxValue;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\Scalar\IntegerRangeInvalidMinValue;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\Scalar\IntegerRangeMissingClosingBracket;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\Scalar\IntegerRangeMissingComma;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\Scalar\IntegerRangeMissingMaxValue;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\Scalar\IntegerRangeMissingMinValue;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\TokenStream;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Type;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\IntegerRangeType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\IntegerValueType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\NativeIntegerType;
use OCA\Talk\Vendor\CuyZ\Valinor\Utility\IsSingleton;

/** @internal */
final class IntegerToken implements TraversingToken
{
    use IsSingleton;

    public function traverse(TokenStream $stream): Type
    {
        if ($stream->done() || ! $stream->next() instanceof OpeningBracketToken) {
            return NativeIntegerType::get();
        }

        $stream->forward();

        if ($stream->done()) {
            throw new IntegerRangeMissingMinValue();
        }

        if ($stream->next()->symbol() === 'min') {
            $min = new IntegerValueType(PHP_INT_MIN);
            $stream->forward();
        } else {
            $min = $stream->read();
        }

        if (! $min instanceof IntegerValueType) {
            throw new IntegerRangeInvalidMinValue($min);
        }

        if ($stream->done() || ! $stream->forward() instanceof CommaToken) {
            throw new IntegerRangeMissingComma($min);
        }

        if ($stream->done()) {
            throw new IntegerRangeMissingMaxValue($min);
        }

        if ($stream->next()->symbol() === 'max') {
            $max = new IntegerValueType(PHP_INT_MAX);
            $stream->forward();
        } else {
            $max = $stream->read();
        }

        if (! $max instanceof IntegerValueType) {
            throw new IntegerRangeInvalidMaxValue($min, $max);
        }

        if ($stream->done() || ! $stream->forward() instanceof ClosingBracketToken) {
            throw new IntegerRangeMissingClosingBracket($min, $max);
        }

        return new IntegerRangeType($min->value(), $max->value());
    }

    public function symbol(): string
    {
        return 'int';
    }
}
