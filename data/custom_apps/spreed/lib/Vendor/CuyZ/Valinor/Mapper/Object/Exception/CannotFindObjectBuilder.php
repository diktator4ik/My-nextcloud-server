<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Object\Exception;

use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Object\ObjectBuilder;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Message\ErrorMessage;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Message\HasParameters;
use OCA\Talk\Vendor\CuyZ\Valinor\Utility\String\StringFormatter;
use OCA\Talk\Vendor\CuyZ\Valinor\Utility\TypeHelper;
use RuntimeException;

use function array_keys;
use function count;
use function ksort;

/** @internal */
final class CannotFindObjectBuilder extends RuntimeException implements ErrorMessage, HasParameters
{
    private string $body = 'Value {source_value} does not match any of {allowed_types}.';

    /** @var array<string, string> */
    private array $parameters;

    /**
     * @param non-empty-list<ObjectBuilder> $builders
     */
    public function __construct(array $builders)
    {
        $this->parameters = [
            'allowed_types' => (function () use ($builders) {
                $signatures = [];
                $sortedSignatures = [];

                foreach ($builders as $builder) {
                    $arguments = $builder->describeArguments();
                    $count = count($arguments);
                    $signature = TypeHelper::dumpArguments($arguments);

                    $signatures[$count][$signature] = null;
                }

                ksort($signatures);

                foreach ($signatures as $list) {
                    foreach (array_keys($list) as $signature) {
                        $sortedSignatures[] = $signature;
                    }
                }

                return implode(', ', $sortedSignatures);
            })(),
        ];

        parent::__construct(StringFormatter::for($this), 1642183169);
    }

    public function body(): string
    {
        return $this->body;
    }

    public function parameters(): array
    {
        return $this->parameters;
    }
}
