<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\Token;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\TokenStream;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Type;

/** @internal */
interface LeftTraversingToken extends Token
{
    public function traverse(Type $type, TokenStream $stream): Type;
}
