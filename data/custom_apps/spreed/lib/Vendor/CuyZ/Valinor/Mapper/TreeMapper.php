<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Mapper;

/** @api */
interface TreeMapper
{
    /**
     * @template T of object
     *
     * @param string|class-string<T> $signature
     * @return T
     * @phpstan-return (
     *     $signature is class-string<T>
     *         ? T
     *         : ($signature is class-string ? object : mixed)
     * )
     *
     * @throws MappingError
     */
    public function map(string $signature, mixed $source): mixed;
}
