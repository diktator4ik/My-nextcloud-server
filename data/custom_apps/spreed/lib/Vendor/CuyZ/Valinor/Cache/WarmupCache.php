<?php

namespace OCA\Talk\Vendor\CuyZ\Valinor\Cache;

use OCA\Talk\Vendor\Psr\SimpleCache\CacheInterface;

/**
 * @internal
 *
 * @template T
 * @extends CacheInterface<T>
 */
interface WarmupCache extends CacheInterface
{
    public function warmup(): void;
}
