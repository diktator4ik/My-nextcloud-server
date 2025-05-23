<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\Token;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\ObjectType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\Scalar\ClassStringClosingBracketMissing;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\Scalar\InvalidClassStringSubType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\TokenStream;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Type;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\ClassStringType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\UnionType;
use OCA\Talk\Vendor\CuyZ\Valinor\Utility\IsSingleton;

/** @internal */
final class ClassStringToken implements TraversingToken
{
    use IsSingleton;

    public function traverse(TokenStream $stream): Type
    {
        if ($stream->done() || ! $stream->next() instanceof OpeningBracketToken) {
            return new ClassStringType();
        }

        $stream->forward();

        $type = $stream->read();

        if (! $type instanceof ObjectType && ! $type instanceof UnionType) {
            throw new InvalidClassStringSubType($type);
        }

        if ($stream->done() || ! $stream->forward() instanceof ClosingBracketToken) {
            throw new ClassStringClosingBracketMissing($type);
        }

        return new ClassStringType($type);
    }

    public function symbol(): string
    {
        return 'class-string';
    }
}
