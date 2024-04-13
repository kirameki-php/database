<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

/**
 * @template-covariant TQueryStatement of QueryStatement
 */
readonly class Executable
{
    /**
     * @param TQueryStatement $statement
     * @param string $template
     * @param list<mixed> $parameters
     */
    public function __construct(
        public QueryStatement $statement,
        public string $template,
        public array $parameters,
    )
    {
    }
}
