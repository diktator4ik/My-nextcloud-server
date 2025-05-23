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
final class InvalidNodeValue extends RuntimeException implements ErrorMessage, HasParameters
{
    private string $body;

    /** @var array<string, string> */
    private array $parameters;

    public function __construct(Type $type)
    {
        $this->parameters = [
            'expected_type' => TypeHelper::dump($type),
        ];

        $this->body = TypeHelper::containsObject($type)
            ? 'Invalid value {source_value}.'
            : 'Value {source_value} does not match type {expected_type}.';

        parent::__construct(StringFormatter::for($this), 1630678334);
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
