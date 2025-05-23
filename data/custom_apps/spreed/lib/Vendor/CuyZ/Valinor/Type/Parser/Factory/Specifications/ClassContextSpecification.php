<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Factory\Specifications;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\Token\ObjectToken;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\Token\TraversingToken;

/** @internal */
final class ClassContextSpecification implements TypeParserSpecification
{
    public function __construct(
        /** @var class-string */
        private string $className,
    ) {}

    public function manipulateToken(TraversingToken $token): TraversingToken
    {
        if ($token->symbol() === 'self' || $token->symbol() === 'static') {
            return new ObjectToken($this->className);
        }

        return $token;
    }
}
