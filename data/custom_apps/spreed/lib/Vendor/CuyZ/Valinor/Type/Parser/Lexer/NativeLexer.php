<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\Token\ArrayToken;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\Token\CallableToken;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\Token\ClassStringToken;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\Token\ClosingBracketToken;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\Token\ClosingCurlyBracketToken;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\Token\ClosingSquareBracketToken;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\Token\ColonToken;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\Token\CommaToken;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\Token\DoubleColonToken;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\Token\FloatValueToken;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\Token\IntegerToken;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\Token\IntegerValueToken;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\Token\IntersectionToken;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\Token\IterableToken;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\Token\ListToken;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\Token\NullableToken;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\Token\ValueOfToken;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\Token\OpeningBracketToken;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\Token\OpeningCurlyBracketToken;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\Token\OpeningSquareBracketToken;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\Token\StringValueToken;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\Token\Token;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\Token\TripleDotsToken;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\Token\TypeToken;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\Token\UnionToken;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\ArrayKeyType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\BooleanValueType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\MixedType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\NativeBooleanType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\NativeFloatType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\NativeStringType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\NegativeIntegerType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\NonEmptyStringType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\NonNegativeIntegerType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\NonPositiveIntegerType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\NullType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\NumericStringType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\PositiveIntegerType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\UndefinedObjectType;

use function filter_var;
use function is_numeric;
use function str_starts_with;
use function strtolower;

/** @internal */
final class NativeLexer implements TypeLexer
{
    public function __construct(private TypeLexer $delegate) {}

    public function tokenize(string $symbol): Token
    {
        return match (strtolower($symbol)) {
            '|' => UnionToken::get(),
            '&' => IntersectionToken::get(),
            '<' => OpeningBracketToken::get(),
            '>' => ClosingBracketToken::get(),
            '[' => OpeningSquareBracketToken::get(),
            ']' => ClosingSquareBracketToken::get(),
            '{' => OpeningCurlyBracketToken::get(),
            '}' => ClosingCurlyBracketToken::get(),
            '::' => DoubleColonToken::get(),
            ':' => ColonToken::get(),
            '?' => NullableToken::get(),
            ',' => CommaToken::get(),
            '...' => TripleDotsToken::get(),

            'int', 'integer' => IntegerToken::get(),
            'array' => ArrayToken::array(),
            'non-empty-array' => ArrayToken::nonEmptyArray(),
            'list' => ListToken::list(),
            'non-empty-list' => ListToken::nonEmptyList(),
            'iterable' => IterableToken::get(),
            'class-string' => ClassStringToken::get(),
            'callable' => CallableToken::get(),
            'value-of' => ValueOfToken::get(),

            'null' => new TypeToken(NullType::get()),
            'true' => new TypeToken(BooleanValueType::true()),
            'false' => new TypeToken(BooleanValueType::false()),
            'mixed' => new TypeToken(MixedType::get()),
            'float' => new TypeToken(NativeFloatType::get()),
            'positive-int' => new TypeToken(PositiveIntegerType::get()),
            'negative-int' => new TypeToken(NegativeIntegerType::get()),
            'non-positive-int' => new TypeToken(NonPositiveIntegerType::get()),
            'non-negative-int' => new TypeToken(NonNegativeIntegerType::get()),
            'string' => new TypeToken(NativeStringType::get()),
            'non-empty-string' => new TypeToken(NonEmptyStringType::get()),
            'numeric-string' => new TypeToken(NumericStringType::get()),
            'bool', 'boolean' => new TypeToken(NativeBooleanType::get()),
            'array-key' => new TypeToken(ArrayKeyType::default()),
            'object' => new TypeToken(UndefinedObjectType::get()),

            default => match (true) {
                str_starts_with($symbol, "'") || str_starts_with($symbol, '"') => new StringValueToken($symbol),
                filter_var($symbol, FILTER_VALIDATE_INT) !== false => new IntegerValueToken((int)$symbol),
                is_numeric($symbol) => new FloatValueToken((float)$symbol),
                default => $this->delegate->tokenize($symbol),
            },
        };
    }
}
