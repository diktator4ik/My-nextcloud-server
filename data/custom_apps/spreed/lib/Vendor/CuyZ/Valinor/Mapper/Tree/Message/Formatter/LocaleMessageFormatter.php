<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Message\Formatter;

use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Message\NodeMessage;

/** @api */
final class LocaleMessageFormatter implements MessageFormatter
{
    public function __construct(private string $locale) {}

    public function format(NodeMessage $message): NodeMessage
    {
        return $message->withLocale($this->locale);
    }
}
