<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Utility\Priority;

/**
 * This interface can be implemented by objects which may be sorted
 * using @see \OCA\Talk\Vendor\CuyZ\Valinor\Utility\Priority\PrioritizedList
 *
 * The higher the priority is for a given object, the more chance it has to
 * be used first.
 *
 * @api
 */
interface HasPriority
{
    public function priority(): int;
}
