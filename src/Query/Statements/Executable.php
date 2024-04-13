<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

readonly class Executable
{
    /**
     * @template TQueryStatement of QueryStatement
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
