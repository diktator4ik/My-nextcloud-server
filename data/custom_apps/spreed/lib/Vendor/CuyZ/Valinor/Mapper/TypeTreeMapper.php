<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Mapper;

use OCA\Talk\Vendor\CuyZ\Valinor\Library\Settings;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Exception\InvalidMappingTypeSignature;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Exception\TypeErrorDuringMapping;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Builder\RootNodeBuilder;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Builder\TreeNode;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Exception\UnresolvableShellType;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Shell;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\InvalidType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\TypeParser;

/** @internal */
final class TypeTreeMapper implements TreeMapper
{
    public function __construct(
        private TypeParser $typeParser,
        private RootNodeBuilder $nodeBuilder,
        private Settings $settings,
    ) {}

    public function map(string $signature, mixed $source): mixed
    {
        $node = $this->node($signature, $source);

        if (! $node->isValid()) {
            throw new TypeTreeMapperError($node->node());
        }

        return $node->value();
    }

    private function node(string $signature, mixed $source): TreeNode
    {
        try {
            $type = $this->typeParser->parse($signature);
        } catch (InvalidType $exception) {
            throw new InvalidMappingTypeSignature($signature, $exception);
        }

        $shell = Shell::root($this->settings, $type, $source);

        try {
            return $this->nodeBuilder->build($shell);
        } catch (UnresolvableShellType $exception) {
            throw new TypeErrorDuringMapping($type, $exception);
        }
    }
}
