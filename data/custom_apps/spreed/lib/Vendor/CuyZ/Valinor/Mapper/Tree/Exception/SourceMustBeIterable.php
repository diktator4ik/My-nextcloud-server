<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Exception;

use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Message\ErrorMessage;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Message\HasParameters;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Type;
use OCA\Talk\Vendor\CuyZ\Valinor\Utility\String\StringFormatter;
use OCA\Talk\Vendor\CuyZ\Valinor\Utility\TypeHelper;
use RuntimeException;

/** @internal */
final class SourceMustBeIterable extends RuntimeException implements ErrorMessage, HasParameters
{
    private string $body;

    /** @var array<string, string> */
    private array $parameters;

    public function __construct(mixed $value, Type $type)
    {
        $this->parameters = [
            'expected_type' => TypeHelper::dump($type),
        ];

        if ($value === null) {
            $this->body = TypeHelper::containsObject($type)
                ? 'Cannot be empty.'
                : 'Cannot be empty and must be filled with a value matching type {expected_type}.';
        } else {
            $this->body = TypeHelper::containsObject($type)
                ? 'Invalid value {source_value}.'
                : 'Value {source_value} does not match type {expected_type}.';
        }

        parent::__construct(StringFormatter::for($this), 1618739163);
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
