<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Definition\Repository\Cache;

use OCA\Talk\Vendor\CuyZ\Valinor\Definition\ClassDefinition;
use OCA\Talk\Vendor\CuyZ\Valinor\Definition\Repository\ClassDefinitionRepository;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\ObjectType;
use OCA\Talk\Vendor\Psr\SimpleCache\CacheInterface;

/** @internal */
final class CacheClassDefinitionRepository implements ClassDefinitionRepository
{
    public function __construct(
        private ClassDefinitionRepository $delegate,
        /** @var CacheInterface<ClassDefinition> */
        private CacheInterface $cache
    ) {}

    public function for(ObjectType $type): ClassDefinition
    {
        // @infection-ignore-all
        $key = "class-definition-\0" . $type->toString();

        $entry = $this->cache->get($key);

        if ($entry) {
            return $entry;
        }

        $class = $this->delegate->for($type);

        $this->cache->set($key, $class);

        return $class;
    }
}
