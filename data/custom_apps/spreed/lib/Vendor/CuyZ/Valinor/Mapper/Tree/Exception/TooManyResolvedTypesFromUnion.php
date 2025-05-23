<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Exception;

use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Message\ErrorMessage;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Message\HasParameters;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\UnionType;
use OCA\Talk\Vendor\CuyZ\Valinor\Utility\String\StringFormatter;
use OCA\Talk\Vendor\CuyZ\Valinor\Utility\TypeHelper;
use RuntimeException;

use function array_map;
use function implode;

/** @internal */
final class TooManyResolvedTypesFromUnion extends RuntimeException implements ErrorMessage, HasParameters
{
    private string $body;

    /** @var array<string, string> */
    private array $parameters;

    public function __construct(UnionType $unionType)
    {
        $this->parameters = [
            'allowed_types' => implode(
                ', ',
                array_map(TypeHelper::dump(...), $unionType->types())
            ),
        ];

        $this->body = TypeHelper::containsObject($unionType)
            ? 'Invalid value {source_value}, it matches two or more types from union: cannot take a decision.'
            : 'Invalid value {source_value}, it matches two or more types from {allowed_types}: cannot take a decision.';

        parent::__construct(StringFormatter::for($this), 1710262975);
    }

    public function body(): string
    {
        return $this->body;
    }

    public function parameters(): array
    {
        return $this->parameters;
    }
}
