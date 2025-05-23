<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Library;

use OCA\Talk\Vendor\CuyZ\Valinor\Cache\ChainCache;
use OCA\Talk\Vendor\CuyZ\Valinor\Cache\KeySanitizerCache;
use OCA\Talk\Vendor\CuyZ\Valinor\Cache\RuntimeCache;
use OCA\Talk\Vendor\CuyZ\Valinor\Cache\Warmup\RecursiveCacheWarmupService;
use OCA\Talk\Vendor\CuyZ\Valinor\Definition\FunctionsContainer;
use OCA\Talk\Vendor\CuyZ\Valinor\Definition\Repository\Cache\CacheClassDefinitionRepository;
use OCA\Talk\Vendor\CuyZ\Valinor\Definition\Repository\Cache\CacheFunctionDefinitionRepository;
use OCA\Talk\Vendor\CuyZ\Valinor\Definition\Repository\ClassDefinitionRepository;
use OCA\Talk\Vendor\CuyZ\Valinor\Definition\Repository\FunctionDefinitionRepository;
use OCA\Talk\Vendor\CuyZ\Valinor\Definition\Repository\Reflection\ReflectionAttributesRepository;
use OCA\Talk\Vendor\CuyZ\Valinor\Definition\Repository\Reflection\ReflectionClassDefinitionRepository;
use OCA\Talk\Vendor\CuyZ\Valinor\Definition\Repository\Reflection\ReflectionFunctionDefinitionRepository;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\ArgumentsMapper;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Object\Factory\CacheObjectBuilderFactory;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Object\Factory\SortingObjectBuilderFactory;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Object\Factory\ConstructorObjectBuilderFactory;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Object\Factory\DateTimeObjectBuilderFactory;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Object\Factory\DateTimeZoneObjectBuilderFactory;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Object\Factory\ObjectBuilderFactory;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Object\Factory\ReflectionObjectBuilderFactory;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Object\Factory\StrictTypesObjectBuilderFactory;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Object\ObjectBuilder;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Builder\ArrayNodeBuilder;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Builder\TypeNodeBuilder;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Builder\InterfaceNodeBuilder;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Builder\ListNodeBuilder;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Builder\ObjectNodeBuilder;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Builder\NodeBuilder;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Builder\NullNodeBuilder;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Builder\ObjectImplementations;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Builder\RootNodeBuilder;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Builder\ScalarNodeBuilder;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Builder\ShapedArrayNodeBuilder;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Builder\MixedNodeBuilder;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Builder\UndefinedObjectNodeBuilder;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Builder\UnionNodeBuilder;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Builder\ValueAlteringNodeBuilder;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\TreeMapper;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\TypeArgumentsMapper;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\TypeTreeMapper;
use OCA\Talk\Vendor\CuyZ\Valinor\Normalizer\ArrayNormalizer;
use OCA\Talk\Vendor\CuyZ\Valinor\Normalizer\Format;
use OCA\Talk\Vendor\CuyZ\Valinor\Normalizer\JsonNormalizer;
use OCA\Talk\Vendor\CuyZ\Valinor\Normalizer\Normalizer;
use OCA\Talk\Vendor\CuyZ\Valinor\Normalizer\Transformer\KeyTransformersHandler;
use OCA\Talk\Vendor\CuyZ\Valinor\Normalizer\Transformer\RecursiveTransformer;
use OCA\Talk\Vendor\CuyZ\Valinor\Normalizer\Transformer\ValueTransformersHandler;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Factory\LexingTypeParserFactory;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\Factory\TypeParserFactory;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Parser\TypeParser;
use OCA\Talk\Vendor\Psr\SimpleCache\CacheInterface;

use function array_keys;
use function call_user_func;
use function count;

/** @internal */
final class Container
{
    /** @var array<class-string, object> */
    private array $services = [];

    /** @var array<class-string, callable(): object> */
    private array $factories;

    public function __construct(Settings $settings)
    {
        $this->factories = [
            TreeMapper::class => fn () => new TypeTreeMapper(
                $this->get(TypeParser::class),
                $this->get(RootNodeBuilder::class),
                $settings,
            ),

            ArgumentsMapper::class => fn () => new TypeArgumentsMapper(
                $this->get(FunctionDefinitionRepository::class),
                $this->get(RootNodeBuilder::class),
                $settings,
            ),

            RootNodeBuilder::class => fn () => new RootNodeBuilder(
                $this->get(NodeBuilder::class)
            ),

            NodeBuilder::class => function () use ($settings) {
                $builder = new TypeNodeBuilder(
                    new ArrayNodeBuilder(),
                    new ListNodeBuilder(),
                    new ShapedArrayNodeBuilder(),
                    new ScalarNodeBuilder(),
                    new UnionNodeBuilder(),
                    new NullNodeBuilder(),
                    new MixedNodeBuilder(),
                    new UndefinedObjectNodeBuilder(),
                    new ObjectNodeBuilder(
                        $this->get(ClassDefinitionRepository::class),
                        $this->get(ObjectBuilderFactory::class),
                        $settings->exceptionFilter,
                    ),
                );

                $builder = new InterfaceNodeBuilder(
                    $builder,
                    $this->get(ObjectImplementations::class),
                    $this->get(ClassDefinitionRepository::class),
                    new FunctionsContainer(
                        $this->get(FunctionDefinitionRepository::class),
                        $settings->customConstructors
                    ),
                    $settings->exceptionFilter,
                );

                if (count($settings->valueModifier) > 0) {
                    $builder = new ValueAlteringNodeBuilder(
                        $builder,
                        new FunctionsContainer(
                            $this->get(FunctionDefinitionRepository::class),
                            $settings->valueModifier
                        )
                    );
                }

                return $builder;
            },

            ObjectImplementations::class => fn () => new ObjectImplementations(
                new FunctionsContainer(
                    $this->get(FunctionDefinitionRepository::class),
                    $settings->inferredMapping
                ),
                $this->get(TypeParser::class),
            ),

            ObjectBuilderFactory::class => function () use ($settings) {
                $constructors = new FunctionsContainer(
                    $this->get(FunctionDefinitionRepository::class),
                    $settings->customConstructors
                );

                $factory = new ReflectionObjectBuilderFactory();
                $factory = new ConstructorObjectBuilderFactory($factory, $settings->nativeConstructors, $constructors);
                $factory = new DateTimeZoneObjectBuilderFactory($factory, $this->get(FunctionDefinitionRepository::class));
                $factory = new DateTimeObjectBuilderFactory($factory, $settings->supportedDateFormats, $this->get(FunctionDefinitionRepository::class));
                $factory = new SortingObjectBuilderFactory($factory);

                if (! $settings->allowPermissiveTypes) {
                    $factory = new StrictTypesObjectBuilderFactory($factory);
                }

                /** @var RuntimeCache<list<ObjectBuilder>> $cache */
                $cache = new RuntimeCache();

                return new CacheObjectBuilderFactory($factory, $cache);
            },

            RecursiveTransformer::class => fn () => new RecursiveTransformer(
                $this->get(ClassDefinitionRepository::class),
                new ValueTransformersHandler(
                    $this->get(FunctionDefinitionRepository::class),
                ),
                new KeyTransformersHandler(),
                $settings->transformersSortedByPriority(),
                array_keys($settings->transformerAttributes),
            ),

            ArrayNormalizer::class => fn () => new ArrayNormalizer(
                $this->get(RecursiveTransformer::class),
            ),

            JsonNormalizer::class => fn () => new JsonNormalizer(
                $this->get(RecursiveTransformer::class),
            ),

            ClassDefinitionRepository::class => fn () => new CacheClassDefinitionRepository(
                new ReflectionClassDefinitionRepository(
                    $this->get(TypeParserFactory::class),
                    $settings->allowedAttributes(),
                ),
                $this->get(CacheInterface::class),
            ),

            FunctionDefinitionRepository::class => fn () => new CacheFunctionDefinitionRepository(
                new ReflectionFunctionDefinitionRepository(
                    $this->get(TypeParserFactory::class),
                    new ReflectionAttributesRepository(
                        $this->get(ClassDefinitionRepository::class),
                        $settings->allowedAttributes(),
                    ),
                ),
                $this->get(CacheInterface::class)
            ),

            TypeParserFactory::class => fn () => new LexingTypeParserFactory(),

            TypeParser::class => fn () => $this->get(TypeParserFactory::class)->buildDefaultTypeParser(),

            RecursiveCacheWarmupService::class => fn () => new RecursiveCacheWarmupService(
                $this->get(TypeParser::class),
                $this->get(CacheInterface::class),
                $this->get(ObjectImplementations::class),
                $this->get(ClassDefinitionRepository::class),
                $this->get(ObjectBuilderFactory::class)
            ),

            CacheInterface::class => function () use ($settings) {
                $cache = new RuntimeCache();

                if (isset($settings->cache)) {
                    $cache = new ChainCache($cache, new KeySanitizerCache($settings->cache));
                }

                return $cache;
            },
        ];
    }

    public function treeMapper(): TreeMapper
    {
        return $this->get(TreeMapper::class);
    }

    public function argumentsMapper(): ArgumentsMapper
    {
        return $this->get(ArgumentsMapper::class);
    }

    /**
     * @template T of Normalizer
     *
     * @param Format<T> $format
     * @return T
     */
    public function normalizer(Format $format): Normalizer
    {
        return $this->get($format->type());
    }

    public function cacheWarmupService(): RecursiveCacheWarmupService
    {
        return $this->get(RecursiveCacheWarmupService::class);
    }

    /**
     * @template T of object
     * @param class-string<T> $name
     * @return T
     */
    private function get(string $name): object
    {
        return $this->services[$name] ??= call_user_func($this->factories[$name]); // @phpstan-ignore-line
    }
}
