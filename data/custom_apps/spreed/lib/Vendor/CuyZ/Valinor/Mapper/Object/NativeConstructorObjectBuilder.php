<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Object;

use OCA\Talk\Vendor\CuyZ\Valinor\Definition\ClassDefinition;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Message\UserlandError;
use Exception;

/** @internal */
final class NativeConstructorObjectBuilder implements ObjectBuilder
{
    private Arguments $arguments;

    public function __construct(private ClassDefinition $class) {}

    public function describeArguments(): Arguments
    {
        return $this->arguments ??= Arguments::fromParameters($this->class->methods->constructor()->parameters);
    }

    public function build(array $arguments): object
    {
        $className = $this->class->name;
        $arguments = new MethodArguments($this->class->methods->constructor()->parameters, $arguments);

        try {
            return new $className(...$arguments);
        } catch (Exception $exception) {
            throw UserlandError::from($exception);
        }
    }

    public function signature(): string
    {
        return $this->class->methods->constructor()->signature;
    }

    public function signatureForArgument(string $argumentName): string
    {
        return $this->class->methods->constructor()->parameters->get($argumentName)->signature;
    }
}
