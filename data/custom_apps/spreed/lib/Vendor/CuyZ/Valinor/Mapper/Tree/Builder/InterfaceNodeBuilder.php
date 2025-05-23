<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Builder;

use OCA\Talk\Vendor\CuyZ\Valinor\Definition\FunctionsContainer;
use OCA\Talk\Vendor\CuyZ\Valinor\Definition\Repository\ClassDefinitionRepository;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Object\Arguments;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Object\ArgumentsValues;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Object\Exception\InvalidSource;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Exception\CannotInferFinalClass;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Exception\CannotResolveObjectType;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Exception\InterfaceHasBothConstructorAndInfer;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Exception\ObjectImplementationCallbackError;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Message\ErrorMessage;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Shell;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Type;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\InterfaceType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\NativeClassType;
use Throwable;

/** @internal */
final class InterfaceNodeBuilder implements NodeBuilder
{
    public function __construct(
        private NodeBuilder $delegate,
        private ObjectImplementations $implementations,
        private ClassDefinitionRepository $classDefinitionRepository,
        private FunctionsContainer $constructors,
        /** @var callable(Throwable): ErrorMessage */
        private mixed $exceptionFilter,
    ) {}

    public function build(Shell $shell, RootNodeBuilder $rootBuilder): TreeNode
    {
        $type = $shell->type();

        if (! $type instanceof InterfaceType && ! $type instanceof NativeClassType) {
            return $this->delegate->build($shell, $rootBuilder);
        }

        if ($this->constructorRegisteredFor($type)) {
            if ($this->implementations->has($type->className())) {
                throw new InterfaceHasBothConstructorAndInfer($type->className());
            }

            return $this->delegate->build($shell, $rootBuilder);
        }

        if ($shell->enableFlexibleCasting() && $shell->value() === null) {
            $shell = $shell->withValue([]);
        } else {
            $shell = $shell->transformIteratorToArray();
        }

        $className = $type->className();

        if (! $this->implementations->has($className)) {
            if ($type instanceof InterfaceType || $this->classDefinitionRepository->for($type)->isAbstract) {
                throw new CannotResolveObjectType($className);
            }

            return $this->delegate->build($shell, $rootBuilder);
        }

        $function = $this->implementations->function($className);
        $arguments = Arguments::fromParameters($function->parameters);

        if ($type instanceof NativeClassType && $this->classDefinitionRepository->for($type)->isFinal) {
            throw new CannotInferFinalClass($type, $function);
        }

        $argumentsValues = ArgumentsValues::forInterface($arguments, $shell);

        if ($argumentsValues->hasInvalidValue()) {
            return TreeNode::error($shell, new InvalidSource($shell->value(), $arguments));
        }

        $children = $this->children($shell, $argumentsValues, $rootBuilder);

        $values = [];

        foreach ($children as $child) {
            if (! $child->isValid()) {
                return TreeNode::branch($shell, null, $children);
            }

            $values[] = $child->value();
        }

        try {
            $classType = $this->implementations->implementation($className, $values);
        } catch (ObjectImplementationCallbackError $exception) {
            $exception = ($this->exceptionFilter)($exception->original());

            return TreeNode::error($shell, $exception);
        }

        $shell = $shell->withType($classType);
        $shell = $shell->withAllowedSuperfluousKeys($arguments->names());

        return $this->delegate->build($shell, $rootBuilder);
    }

    private function constructorRegisteredFor(Type $type): bool
    {
        foreach ($this->constructors as $constructor) {
            if ($type->matches($constructor->definition->returnType)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<TreeNode>
     */
    private function children(Shell $shell, ArgumentsValues $arguments, RootNodeBuilder $rootBuilder): array
    {
        $children = [];

        foreach ($arguments as $argument) {
            $name = $argument->name();
            $type = $argument->type();
            $attributes = $argument->attributes();

            $child = $shell->child($name, $type, $attributes);

            if ($arguments->hasValue($name)) {
                $child = $child->withValue($arguments->getValue($name));
            }

            $children[] = $rootBuilder->build($child);
        }

        return $children;
    }
}
