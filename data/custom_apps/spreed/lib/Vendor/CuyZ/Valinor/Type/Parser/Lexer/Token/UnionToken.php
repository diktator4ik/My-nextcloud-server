<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\Token;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\RightUnionTypeMissing;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\TokenStream;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Type;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\UnionType;
use OCA\Talk\Vendor\CuyZ\Valinor\Utility\IsSingleton;

/** @internal */
final class UnionToken implements LeftTraversingToken
{
    use IsSingleton;

    public function traverse(Type $type, TokenStream $stream): Type
    {
        if ($stream->done()) {
            throw new RightUnionTypeMissing($type);
        }

        return new UnionType($type, $stream->read());
    }

    public function symbol(): string
    {
        return '|';
    }
}
