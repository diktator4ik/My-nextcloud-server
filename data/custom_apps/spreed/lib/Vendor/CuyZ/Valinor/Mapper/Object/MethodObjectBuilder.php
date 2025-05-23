<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Object;

use OCA\Talk\Vendor\CuyZ\Valinor\Definition\Parameters;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Message\UserlandError;
use Exception;

/** @internal */
final class MethodObjectBuilder implements ObjectBuilder
{
    private Arguments $arguments;

    public function __construct(
        private string $className,
        private string $methodName,
        private Parameters $parameters
    ) {}

    public function describeArguments(): Arguments
    {
        return $this->arguments ??= Arguments::fromParameters($this->parameters);
    }

    public function build(array $arguments): object
    {
        $methodName = $this->methodName;
        $arguments = new MethodArguments($this->parameters, $arguments);

        try {
            return ($this->className)::$methodName(...$arguments); // @phpstan-ignore-line
        } catch (Exception $exception) {
            throw UserlandError::from($exception);
        }
    }

    public function signature(): string
    {
        return "$this->className::$this->methodName()";
    }

    public function signatureForArgument(string $argumentName): string
    {
        return $this->parameters->get($argumentName)->signature;
    }
}
