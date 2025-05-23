<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Object\Factory;

use OCA\Talk\Vendor\CuyZ\Valinor\Definition\ClassDefinition;
use OCA\Talk\Vendor\CuyZ\Valinor\Definition\FunctionObject;
use OCA\Talk\Vendor\CuyZ\Valinor\Definition\Repository\FunctionDefinitionRepository;
use OCA\Talk\Vendor\CuyZ\Valinor\Library\Settings;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Object\DateTimeFormatConstructor;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Object\FunctionObjectBuilder;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Object\NativeConstructorObjectBuilder;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Object\ObjectBuilder;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\ObjectType;
use DateTime;
use DateTimeImmutable;

use function array_filter;
use function count;

/** @internal */
final class DateTimeObjectBuilderFactory implements ObjectBuilderFactory
{
    public function __construct(
        private ObjectBuilderFactory $delegate,
        /** @var non-empty-list<non-empty-string> */
        private array $supportedDateFormats,
        private FunctionDefinitionRepository $functionDefinitionRepository
    ) {}

    public function for(ClassDefinition $class): array
    {
        $className = $class->name;

        $builders = $this->delegate->for($class);

        if ($className !== DateTime::class && $className !== DateTimeImmutable::class) {
            return $builders;
        }

        // Remove `DateTime` & `DateTimeImmutable` native constructors
        $builders = array_filter($builders, fn (ObjectBuilder $builder) => ! $builder instanceof NativeConstructorObjectBuilder);

        $buildersWithOneArgument = array_filter($builders, fn (ObjectBuilder $builder) => count($builder->describeArguments()) === 1);

        if (count($buildersWithOneArgument) === 0 || $this->supportedDateFormats !== Settings::DEFAULT_SUPPORTED_DATETIME_FORMATS) {
            $builders[] = $this->internalDateTimeBuilder($class->type);
        }

        /** @var non-empty-list<ObjectBuilder> */
        return $builders;
    }

    private function internalDateTimeBuilder(ObjectType $type): FunctionObjectBuilder
    {
        $constructor = new DateTimeFormatConstructor(...$this->supportedDateFormats);
        $function = new FunctionObject($this->functionDefinitionRepository->for($constructor), $constructor);

        return new FunctionObjectBuilder($function, $type);
    }
}
