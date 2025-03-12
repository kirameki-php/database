<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Closure;
use Kirameki\Database\Query\QueryHandler;

readonly class WithBuilder
{
    /**
     * @var With
     */
    public protected(set) With $with;

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
        $this->with = new With($name, $recursive);
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
            $subquery = clone $subquery->statement;
        }

        $this->with->statement = $subquery;
        return $this;
    }
}
