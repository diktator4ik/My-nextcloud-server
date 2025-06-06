<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Normalizer\Transformer;

use OCA\Talk\Vendor\CuyZ\Valinor\Definition\AttributeDefinition;
use OCA\Talk\Vendor\CuyZ\Valinor\Definition\FunctionDefinition;
use OCA\Talk\Vendor\CuyZ\Valinor\Definition\MethodDefinition;
use OCA\Talk\Vendor\CuyZ\Valinor\Definition\Repository\FunctionDefinitionRepository;
use OCA\Talk\Vendor\CuyZ\Valinor\Normalizer\Exception\TransformerHasInvalidCallableParameter;
use OCA\Talk\Vendor\CuyZ\Valinor\Normalizer\Exception\TransformerHasNoParameter;
use OCA\Talk\Vendor\CuyZ\Valinor\Normalizer\Exception\TransformerHasTooManyParameters;
use OCA\Talk\Vendor\CuyZ\Valinor\Type\Types\CallableType;

use function array_shift;
use function call_user_func;

/** @internal */
final class ValueTransformersHandler
{
    /** @var array<string, true> */
    private array $transformerCheck = [];

    public function __construct(
        private FunctionDefinitionRepository $functionDefinitionRepository,
    ) {}

    /**
     * @param array<AttributeDefinition> $attributes
     * @param list<callable> $transformers
     * @return array<mixed>|scalar|null
     */
    public function transform(mixed $value, array $attributes, array $transformers, callable $defaultTransformer): mixed
    {
        /** @var array<mixed>|scalar|null */
        return call_user_func(
            $this->next($transformers, $value, $attributes, $defaultTransformer),
        );
    }

    /**
     * @param list<callable> $transformers
     * @param array<AttributeDefinition> $attributes
     */
    private function next(array $transformers, mixed $value, array $attributes, callable $defaultTransformer): callable
    {
        if ($attributes !== []) {
            return $this->nextAttribute(
                $value,
                $attributes,
                fn () => call_user_func($this->next($transformers, $value, [], $defaultTransformer)),
            );
        }

        $transformer = array_shift($transformers);

        if ($transformer === null) {
            return fn () => $defaultTransformer($value);
        }

        $function = $this->functionDefinitionRepository->for($transformer);

        $this->checkTransformer($function);

        if (! $function->parameters->at(0)->type->accepts($value)) {
            return $this->next($transformers, $value, [], $defaultTransformer);
        }

        return fn () => $transformer($value, fn () => call_user_func($this->next($transformers, $value, [], $defaultTransformer)));
    }

    /**
     * @param array<AttributeDefinition> $attributes
     */
    private function nextAttribute(mixed $value, array $attributes, callable $next): callable
    {
        $attribute = array_shift($attributes);

        if ($attribute === null) {
            return $next;
        }

        if (! $attribute->class->methods->has('normalize')) {
            return $this->nextAttribute($value, $attributes, $next);
        }

        $method = $attribute->class->methods->get('normalize');

        $this->checkTransformer($method);

        if (! $method->parameters->at(0)->type->accepts($value)) {
            return $this->nextAttribute($value, $attributes, $next);
        }

        // @phpstan-ignore-next-line / We know the method exists
        return fn () => $attribute->instantiate()->normalize(
            $value,
            fn () => call_user_func($this->nextAttribute($value, $attributes, $next))
        );
    }

    private function checkTransformer(MethodDefinition|FunctionDefinition $method): void
    {
        if (isset($this->transformerCheck[$method->signature])) {
            return;
        }

        // @infection-ignore-all
        $this->transformerCheck[$method->signature] = true;

        $parameters = $method->parameters;

        if ($parameters->count() === 0) {
            throw new TransformerHasNoParameter($method);
        }

        if ($parameters->count() > 2) {
            throw new TransformerHasTooManyParameters($method);
        }

        if ($parameters->count() > 1 && ! $parameters->at(1)->nativeType instanceof CallableType) {
            throw new TransformerHasInvalidCallableParameter($method, $parameters->at(1)->nativeType);
        }
    }
}
