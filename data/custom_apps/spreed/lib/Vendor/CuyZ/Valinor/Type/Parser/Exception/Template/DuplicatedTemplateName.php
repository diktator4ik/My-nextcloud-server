<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\Template;

use LogicException;

/** @internal */
final class DuplicatedTemplateName extends LogicException
{
    /**
     * @param class-string $className
     */
    public function __construct(string $className, string $template)
    {
        parent::__construct(
            "The template `$template` in class `$className` was defined at least twice.",
            1604612898
        );
    }
}
