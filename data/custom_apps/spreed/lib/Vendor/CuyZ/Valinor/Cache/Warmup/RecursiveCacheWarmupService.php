<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Cache\Warmup;

use OCA\Talk\Vendor\CuyZ\Valinor\Cache\Exception\InvalidSignatureToWarmup;
use OCA\Talk\Vendor\CuyZ\Valinor\Cache\WarmupCache;
use OCA\Talk\Vendor\CuyZ\Valinor\Definition\Repository\ClassDefinitionRepository;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Object\Factory\ObjectBuilderFactory;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Builder\ObjectImplementations;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\ClassType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\CompositeType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Exception\InvalidType;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\TypeParser;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Type;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\InterfaceType;
use OCA\Talk\Vendor\Psr\SimpleCache\CacheInterface;

use function in_array;

/** @internal */
final class RecursiveCacheWarmupService
{
    /** @var list<class-string> */
    private array $classesWarmedUp = [];

    private bool $warmupWasDone = false;

    public function __construct(
        private TypeParser $parser,
        /** @var CacheInterface<mixed> */
        private CacheInterface $cache,
        private ObjectImplementations $implementations,
        private ClassDefinitionRepository $classDefinitionRepository,
        private ObjectBuilderFactory $objectBuilderFactory
    ) {}

    public function warmup(string ...$signatures): void
    {
        if (! $this->warmupWasDone) {
            $this->warmupWasDone = true;

            if ($this->cache instanceof WarmupCache) {
                $this->cache->warmup();
            }
        }

        foreach ($signatures as $signature) {
            try {
                $this->warmupType($this->parser->parse($signature));
            } catch (InvalidType $exception) {
                throw new InvalidSignatureToWarmup($signature, $exception);
            }
        }
    }

    private function warmupType(Type $type): void
    {
        if ($type instanceof InterfaceType) {
            $this->warmupInterfaceType($type);
        }

        if ($type instanceof ClassType) {
            $this->warmupClassType($type);
        }

        if ($type instanceof CompositeType) {
            foreach ($type->traverse() as $subType) {
                $this->warmupType($subType);
            }
        }
    }

    private function warmupInterfaceType(InterfaceType $type): void
    {
        $interfaceName = $type->className();

        if (! $this->implementations->has($interfaceName)) {
            return;
        }

        $function = $this->implementations->function($interfaceName);

        $this->warmupType($function->returnType);

        foreach ($function->parameters as $parameter) {
            $this->warmupType($parameter->type);
        }
    }

    private function warmupClassType(ClassType $type): void
    {
        if (in_array($type->className(), $this->classesWarmedUp, true)) {
            return;
        }

        $this->classesWarmedUp[] = $type->className();

        $classDefinition = $this->classDefinitionRepository->for($type);
        $objectBuilders = $this->objectBuilderFactory->for($classDefinition);

        foreach ($objectBuilders as $builder) {
            foreach ($builder->describeArguments() as $argument) {
                $this->warmupType($argument->type());
            }
        }
    }
}
