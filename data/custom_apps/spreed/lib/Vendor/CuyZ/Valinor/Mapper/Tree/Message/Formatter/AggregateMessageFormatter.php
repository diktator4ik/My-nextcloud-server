<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Message\Formatter;

use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Message\NodeMessage;

/** @api */
final class AggregateMessageFormatter implements MessageFormatter
{
    /** @var MessageFormatter[] */
    private array $formatters;

    public function __construct(MessageFormatter ...$formatters)
    {
        $this->formatters = $formatters;
    }

    public function format(NodeMessage $message): NodeMessage
    {
        foreach ($this->formatters as $formatter) {
            $message = $formatter->format($message);
        }

        return $message;
    }
}
