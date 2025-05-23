<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\Token\Token;

/** @internal */
interface TypeLexer
{
    public function tokenize(string $symbol): Token;
}
