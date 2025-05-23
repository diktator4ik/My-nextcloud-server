<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Mapper;

use OCA\Talk\Vendor\CuyZ\Valinor\Definition\FunctionDefinition;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Message\Messages;
use OCA\Talk\Vendor\CuyZ\Valinor\Mapper\Tree\Node;
use OCA\Talk\Vendor\CuyZ\Valinor\Utility\ValueDumper;
use RuntimeException;

/** @internal */
final class ArgumentsMapperError extends RuntimeException implements MappingError
{
    private Node $node;

    public function __construct(FunctionDefinition $function, Node $node)
    {
        $this->node = $node;

        $errors = Messages::flattenFromNode($node)->errors();
        $errorsCount = count($errors);

        if ($errorsCount === 1) {
            $body = $errors
                ->toArray()[0]
                ->withBody("Could not map arguments of `$function->signature`. An error occurred at path {node_path}: {original_message}")
                ->toString();
        } else {
            $source = ValueDumper::dump($node->sourceValue());
            $body = "Could not map arguments of `$function->signature` with value $source. A total of $errorsCount errors were encountered.";
        }

        parent::__construct($body, 1671115362);
    }

    public function node(): Node
    {
        return $this->node;
    }
}
