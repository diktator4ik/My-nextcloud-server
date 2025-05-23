<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Builder;

use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Exception\CannotMapToPermissiveType;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Shell;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\UndefinedObjectType;

use function assert;

/** @internal */
final class UndefinedObjectNodeBuilder implements NodeBuilder
{
    public function build(Shell $shell, RootNodeBuilder $rootBuilder): TreeNode
    {
        assert($shell->type() instanceof UndefinedObjectType);

        if (! $shell->allowPermissiveTypes()) {
            throw new CannotMapToPermissiveType($shell);
        }

        return TreeNode::leaf($shell, $shell->value());
    }
}
