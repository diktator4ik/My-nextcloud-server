<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\Token;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\TokenStream;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Type;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\FloatValueType;

/** @internal */
final class FloatValueToken implements TraversingToken
{
    public function __construct(private float $value) {}

    public function traverse(TokenStream $stream): Type
    {
        return new FloatValueType($this->value);
    }

    public function symbol(): string
    {
        return (string)$this->value;
    }
}
