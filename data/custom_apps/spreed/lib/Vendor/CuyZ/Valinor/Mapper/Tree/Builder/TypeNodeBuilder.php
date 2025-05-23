<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Builder;

use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Shell;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\ArrayType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\EnumType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\InterfaceType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\IterableType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\ListType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\MixedType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\NativeClassType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\NonEmptyArrayType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\NonEmptyListType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\NullType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\ShapedArrayType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\UndefinedObjectType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\UnionType;

/** @internal */
final class TypeNodeBuilder implements NodeBuilder
{
    public function __construct(
        private ArrayNodeBuilder $arrayNodeBuilder,
        private ListNodeBuilder $listNodeBuilder,
        private ShapedArrayNodeBuilder $shapedArrayNodeBuilder,
        private ScalarNodeBuilder $scalarNodeBuilder,
        private UnionNodeBuilder $unionNodeBuilder,
        private NullNodeBuilder $nullNodeBuilder,
        private MixedNodeBuilder $mixedNodeBuilder,
        private UndefinedObjectNodeBuilder $undefinedObjectNodeBuilder,
        private ObjectNodeBuilder $objectNodeBuilder,
    ) {}

    public function build(Shell $shell, RootNodeBuilder $rootBuilder): TreeNode
    {
        $builder = match ($shell->type()::class) {
            // List
            ListType::class,
            NonEmptyListType::class => $this->listNodeBuilder,

            // Array
            ArrayType::class,
            NonEmptyArrayType::class,
            IterableType::class => $this->arrayNodeBuilder,

            // ShapedArray
            ShapedArrayType::class => $this->shapedArrayNodeBuilder,

            // Union
            UnionType::class => $this->unionNodeBuilder,

            // Null
            NullType::class => $this->nullNodeBuilder,

            // Mixed
            MixedType::class => $this->mixedNodeBuilder,

            // Undefined object
            UndefinedObjectType::class => $this->undefinedObjectNodeBuilder,

            // Object
            NativeClassType::class,
            EnumType::class,
            InterfaceType::class => $this->objectNodeBuilder,

            // Scalar
            default => $this->scalarNodeBuilder,
        };

        return $builder->build($shell, $rootBuilder);
    }
}
