<?php

namespace OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Builder;

use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Shell;

/** @internal */
interface NodeBuilder
{
    public function build(Shell $shell, RootNodeBuilder $rootBuilder): TreeNode;
}
