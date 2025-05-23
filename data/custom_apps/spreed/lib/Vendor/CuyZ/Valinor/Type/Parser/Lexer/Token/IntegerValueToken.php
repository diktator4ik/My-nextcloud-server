<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\Token;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\TokenStream;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Type;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\IntegerValueType;

/** @internal */
final class IntegerValueToken implements TraversingToken
{
    public function __construct(private int $value) {}

    public function traverse(TokenStream $stream): Type
    {
        return new IntegerValueType($this->value);
    }

    public function symbol(): string
    {
        return (string)$this->value;
    }
}
