<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\Token;

use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\UnknownSymbol;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Factory\Specifications\TypeParserSpecification;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Lexer\TokenStream;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Type;
use OCA\Talk\Vendor\CuyZ\Valinor\Utility\Reflection\Reflection;

/** @internal */
final class VacantToken implements TraversingToken
{
    public function __construct(
        private string $symbol,
        /** @var array<TypeParserSpecification> */
        private array $specifications,
    ) {}

    public function traverse(TokenStream $stream): Type
    {
        $token = $this;

        foreach ($this->specifications as $specification) {
            $new = $specification->manipulateToken($token);

            if ($new !== $token) {
                return $new->traverse($stream);
            }
        }

        if (Reflection::enumExists($this->symbol)) {
            return (new EnumNameToken($this->symbol))->traverse($stream);
        }

        if (Reflection::classOrInterfaceExists($this->symbol)) {
            return (new ClassNameToken($this->symbol))->traverse($stream);
        }

        throw new UnknownSymbol($this->symbol);
    }

    public function symbol(): string
    {
        return $this->symbol;
    }
}
