<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\Token;

use OCA\Talk\Vendor\CuyZ\Valinor\Utility\IsSingleton;

/** @internal */
final class OpeningBracketToken implements Token
{
    use IsSingleton;

    public function symbol(): string
    {
        return '<';
    }
}
