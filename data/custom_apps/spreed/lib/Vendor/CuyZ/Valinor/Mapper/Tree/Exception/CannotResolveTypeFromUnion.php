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
final class CannotResolveTypeFromUnion extends RuntimeException implements ErrorMessage, HasParameters
{
    private string $body;

    /** @var array<string, string> */
    private array $parameters;

    public function __construct(mixed $source, UnionType $unionType)
    {
        $this->parameters = [
            'allowed_types' => implode(
                ', ',
                array_map(TypeHelper::dump(...), $unionType->types())
            ),
        ];

        if ($source === null) {
            $this->body = TypeHelper::containsObject($unionType)
                ? 'Cannot be empty.'
                : 'Cannot be empty and must be filled with a value matching any of {allowed_types}.';
        } else {
            $this->body = TypeHelper::containsObject($unionType)
                ? 'Invalid value {source_value}.'
                : 'Value {source_value} does not match any of {allowed_types}.';
        }

        parent::__construct(StringFormatter::for($this), 1607027306);
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
