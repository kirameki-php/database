<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\QueryHandler;

/**
 * @extends QueryBuilder<RawStatement>
 */
class RawBuilder extends QueryBuilder
{
    /**
     * @param QueryHandler $handler
     * @param string $template
     * @param iterable<int, mixed> $parameters
     */
    public function __construct(
        QueryHandler $handler,
        string $template,
        iterable $parameters = [],
    )
    {
        parent::__construct($handler, new RawStatement($template, $parameters));
    }
}
