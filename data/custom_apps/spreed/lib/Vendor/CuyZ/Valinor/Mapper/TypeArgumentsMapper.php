<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Mapper;

use OCA\Talk\Vendor\CuyZ\Valinor\Definition\ParameterDefinition;
use OCA\Talk\Vendor\CuyZ\Valinor\Definition\Repository\FunctionDefinitionRepository;
use OCA\Talk\Vendor\CuyZ\Valinor\Library\Settings;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Exception\TypeErrorDuringArgumentsMapping;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Builder\RootNodeBuilder;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Exception\UnresolvableShellType;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Shell;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\ShapedArrayElement;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\ShapedArrayType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\StringValueType;

/** @internal */
final class TypeArgumentsMapper implements ArgumentsMapper
{
    public function __construct(
        private FunctionDefinitionRepository $functionDefinitionRepository,
        private RootNodeBuilder $nodeBuilder,
        private Settings $settings,
    ) {}

    public function mapArguments(callable $callable, mixed $source): array
    {
        $function = $this->functionDefinitionRepository->for($callable);

        $elements = array_map(
            fn (ParameterDefinition $parameter) => new ShapedArrayElement(
                new StringValueType($parameter->name),
                $parameter->type,
                $parameter->isOptional
            ),
            iterator_to_array($function->parameters)
        );

        $type = new ShapedArrayType(...$elements);
        $shell = Shell::root($this->settings, $type, $source);

        try {
            $node = $this->nodeBuilder->build($shell);
        } catch (UnresolvableShellType $exception) {
            throw new TypeErrorDuringArgumentsMapping($function, $exception);
        }

        if (! $node->isValid()) {
            throw new ArgumentsMapperError($function, $node->node());
        }

        /** @var array<string, mixed> */
        return $node->value();
    }
}
