<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Object;

use Countable;
use OCA\Talk\Vendor\CuyZ\Valinor\Definition\ParameterDefinition;
use OCA\Talk\Vendor\CuyZ\Valinor\Definition\Parameters;
use OCA\Talk\Vendor\CuyZ\Valinor\Definition\Properties;
use OCA\Talk\Vendor\CuyZ\Valinor\Definition\PropertyDefinition;
use IteratorAggregate;
use Traversable;

use function array_keys;
use function array_map;
use function array_values;
use function count;

/**
 * @internal
 *
 * @implements IteratorAggregate<Argument>
 */
final class Arguments implements IteratorAggregate, Countable
{
    /** @var array<string, Argument> */
    private array $arguments = [];

    public function __construct(Argument ...$arguments)
    {
        foreach ($arguments as $argument) {
            $this->arguments[$argument->name()] = $argument;
        }
    }

    public static function fromParameters(Parameters $parameters): self
    {
        return new self(...array_map(
            fn (ParameterDefinition $parameter) => Argument::fromParameter($parameter),
            [...$parameters],
        ));
    }

    public static function fromProperties(Properties $properties): self
    {
        return new self(...array_map(
            fn (PropertyDefinition $property) => Argument::fromProperty($property),
            [...$properties],
        ));
    }

    public function at(int $index): Argument
    {
        return array_values($this->arguments)[$index];
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_keys($this->arguments);
    }

    /**
     * @return array<string, Argument>
     */
    public function toArray(): array
    {
        return $this->arguments;
    }

    public function count(): int
    {
        return count($this->arguments);
    }

    /**
     * @return Traversable<Argument>
     */
    public function getIterator(): Traversable
    {
        yield from $this->arguments;
    }
}
