<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Builder;

use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Exception\InvalidIterableKeyType;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Exception\InvalidTraversableKey;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Exception\SourceMustBeIterable;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Shell;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\CompositeTraversableType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\ArrayType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\IterableType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\NonEmptyArrayType;

use function assert;
use function is_int;
use function is_iterable;
use function is_string;

/** @internal */
final class ArrayNodeBuilder implements NodeBuilder
{
    public function build(Shell $shell, RootNodeBuilder $rootBuilder): TreeNode
    {
        $type = $shell->type();
        $value = $shell->value();

        assert($type instanceof ArrayType || $type instanceof NonEmptyArrayType || $type instanceof IterableType);

        if ($shell->enableFlexibleCasting() && $value === null) {
            return TreeNode::branch($shell, [], []);
        }

        if (! is_iterable($value)) {
            return TreeNode::error($shell, new SourceMustBeIterable($value, $type));
        }

        $children = $this->children($type, $shell, $rootBuilder);
        $array = $this->buildArray($children);

        return TreeNode::branch($shell, $array, $children);
    }

    /**
     * @return array<TreeNode>
     */
    private function children(CompositeTraversableType $type, Shell $shell, RootNodeBuilder $rootBuilder): array
    {
        /** @var iterable<mixed> $values */
        $values = $shell->value();
        $keyType = $type->keyType();
        $subType = $type->subType();

        $children = [];

        foreach ($values as $key => $value) {
            if (! is_string($key) && ! is_int($key)) {
                throw new InvalidIterableKeyType($key, $shell->path());
            }

            $child = $shell->child((string)$key, $subType);

            if (! $keyType->accepts($key)) {
                $children[$key] = TreeNode::error($child, new InvalidTraversableKey($key, $keyType));
            } else {
                $children[$key] = $rootBuilder->build($child->withValue($value));
            }
        }

        return $children;
    }

    /**
     * @param array<TreeNode> $children
     * @return mixed[]|null
     */
    private function buildArray(array $children): ?array
    {
        $array = [];

        foreach ($children as $key => $child) {
            if (! $child->isValid()) {
                return null;
            }

            $array[$key] = $child->value();
        }

        return $array;
    }
}
