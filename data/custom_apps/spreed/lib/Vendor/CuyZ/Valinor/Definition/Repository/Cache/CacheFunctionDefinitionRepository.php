<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Definition\Repository\Cache;

use OCA\Talk\Vendor\CuyZ\Valinor\Definition\FunctionDefinition;
use OCA\Talk\Vendor\CuyZ\Valinor\Definition\Repository\FunctionDefinitionRepository;
use OCA\Talk\Vendor\CuyZ\Valinor\Utility\Reflection\Reflection;
use OCA\Talk\Vendor\Psr\SimpleCache\CacheInterface;

/** @internal */
final class CacheFunctionDefinitionRepository implements FunctionDefinitionRepository
{
    public function __construct(
        private FunctionDefinitionRepository $delegate,
        /** @var CacheInterface<FunctionDefinition> */
        private CacheInterface $cache
    ) {}

    public function for(callable $function): FunctionDefinition
    {
        $reflection = Reflection::function($function);

        // @infection-ignore-all
        $key = "function-definition-\0" . $reflection->getFileName() . ':' . $reflection->getStartLine() . '-' . $reflection->getEndLine();

        $entry = $this->cache->get($key);

        if ($entry) {
            return $entry;
        }

        $definition = $this->delegate->for($function);

        $this->cache->set($key, $definition);

        return $definition;
    }
}
