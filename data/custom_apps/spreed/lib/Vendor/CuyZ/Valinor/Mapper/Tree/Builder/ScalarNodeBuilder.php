<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Builder;

use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Shell;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\ScalarType;

use function assert;

/** @internal */
final class ScalarNodeBuilder implements NodeBuilder
{
    public function build(Shell $shell, RootNodeBuilder $rootBuilder): TreeNode
    {
        $type = $shell->type();
        $value = $shell->value();

        assert($type instanceof ScalarType);

        if ($type->accepts($value)) {
            return TreeNode::leaf($shell, $value);
        }

        if (! $shell->enableFlexibleCasting() || ! $type->canCast($value)) {
            return TreeNode::error($shell, $type->errorMessage());
        }

        return TreeNode::leaf($shell, $type->cast($value));
    }
}
