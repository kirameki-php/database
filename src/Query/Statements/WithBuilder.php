<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Closure;
use Kirameki\Database\Query\QueryHandler;

readonly class WithBuilder
{
    /**
     * @var WithDefinition
     */
    protected WithDefinition $definition;

    /**
     * @param QueryHandler $handler
     * @param string $name
     * @param bool $recursive
     */
    public function __construct(
        protected QueryHandler $handler,
        string $name,
        bool $recursive = false,
    )
    {
        $this->definition = new WithDefinition($name, $recursive);
    }

    /**
     * @param SelectBuilder|SelectStatement|Closure(SelectBuilder): SelectBuilder $subquery
     * @return $this
     */
    public function as(SelectBuilder|SelectStatement|Closure $subquery): static
    {
        if ($subquery instanceof Closure) {
            $subquery = $subquery(new SelectBuilder($this->handler));
        }

        if ($subquery instanceof SelectBuilder) {
            $subquery = $subquery->statement;
        }

        $this->definition->statement = $subquery;
        return $this;
    }

    /**
     * @return WithDefinition
     */
    public function getDefinition(): WithDefinition
    {
        return $this->definition;
    }
}
