<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\Token;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\TokenStream;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Type;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\CallableType;
use OCA\Talk\Vendor\CuyZ\Valinor\Utility\IsSingleton;

/** @internal */
final class CallableToken implements TraversingToken
{
    use IsSingleton;

    public function traverse(TokenStream $stream): Type
    {
        return CallableType::get();
    }

    public function symbol(): string
    {
        return 'callable';
    }
}
