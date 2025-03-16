<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Closure;
use Kirameki\Database\Query\QueryHandler;

class WithRecursiveBuilder extends CteBuilder
{
    /**
     * @param QueryHandler $handler
     */
    public function __construct(QueryHandler $handler)
    {
        parent::__construct($handler, true);
    }

    /**
     * @param string $name
     * @param list<string> $columns
     * @param SelectBuilder|CompoundBuilder|Closure(SelectBuilder): mixed|null $as
     * @return static
     */
    public function withRecursive(
        string $name,
        iterable $columns = [],
        SelectBuilder|CompoundBuilder|Closure|null $as = null,
    ): static
    {
        return $this->addCte($name, $columns, $as);
    }
}
