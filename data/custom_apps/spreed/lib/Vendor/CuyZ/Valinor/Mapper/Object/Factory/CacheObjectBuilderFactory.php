<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Object\Factory;

use OCA\Talk\Vendor\CuyZ\Valinor\Definition\ClassDefinition;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Object\ObjectBuilder;
use OCA\Talk\Vendor\Psr\SimpleCache\CacheInterface;

/** @internal */
final class CacheObjectBuilderFactory implements ObjectBuilderFactory
{
    public function __construct(
        private ObjectBuilderFactory $delegate,
        /** @var CacheInterface<list<ObjectBuilder>> */
        private CacheInterface $cache
    ) {}

    public function for(ClassDefinition $class): array
    {
        $signature = $class->type->toString();

        $entry = $this->cache->get($signature);

        if ($entry) {
            return $entry;
        }

        $builders = $this->delegate->for($class);

        $this->cache->set($signature, $builders);

        return $builders;
    }
}
