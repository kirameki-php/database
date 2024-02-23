<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Builders;

use Closure;
use Kirameki\Database\Connection;
use Kirameki\Database\Query\Expressions\Aggregate;
use Kirameki\Database\Query\Statements\ConditionDefinition;
use Kirameki\Database\Query\Statements\SelectStatement;
use Kirameki\Database\Query\Support\JoinType;
use Kirameki\Database\Query\Support\LockOption;
use Kirameki\Database\Query\Support\LockType;
use Kirameki\Database\Query\Expressions\Expr;
use function is_array;

/**
 * @property SelectStatement $statement
 */
class SelectBuilder extends ConditionsBuilder
{
    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        parent::__construct($connection, new SelectStatement());
    }

    #region selecting --------------------------------------------------------------------------------------------------

    /**
     * @param string|Expr ...$tables
     * @return $this
     */
    public function from(string|Expr ...$tables): static
    {
        $this->statement->tables = $tables;
        return $this;
    }

    /**
     * @param string|Expr ...$columns
     * @return $this
     */
    public function columns(string|Expr ...$columns): static
    {
        $this->statement->columns = $columns;
        return $this;
    }

    /**
     * @return $this
     */
    public function distinct(): static
    {
        $this->statement->distinct = true;
        return $this;
    }

    /**
     * @param string|Expr $column
     * @return $this
     */
    protected function addToSelect(string|Expr $column): static
    {
        $this->statement->columns[]= $column;
        return $this;
    }

    #endregion selecting -----------------------------------------------------------------------------------------------

    #region locking ----------------------------------------------------------------------------------------------------

    /**
     * @return $this
     */
    public function forShare(): static
    {
        $this->statement->lockType = LockType::Shared;
        $this->statement->lockOption = null;
        return $this;
    }

    /**
     * @param LockOption|null $option
     * @return $this
     */
    public function forUpdate(?LockOption $option = null): static
    {
        $this->statement->lockType = LockType::Exclusive;
        $this->statement->lockOption = $option;
        return $this;
    }

    #endregion locking -------------------------------------------------------------------------------------------------

    #region join ------------------------------------------------------------------------------------------------------

    /**
     * @param string $table
     * @param Closure(JoinBuilder): JoinBuilder $callback
     * @return $this
     */
    public function join(string $table, Closure $callback): static
    {
        return $this->addJoinToStatement($callback(new JoinBuilder(JoinType::Inner, $table)));
    }

    /**
     * @param string $table
     * @param string $column1
     * @param string $column2
     * @return $this
     */
    public function joinOn(string $table, string $column1, string $column2): static
    {
        return $this->addJoinToStatement((new JoinBuilder(JoinType::Inner, $table))->on($column1, $column2));
    }

    /**
     * @param string $table
     * @param Closure(JoinBuilder): JoinBuilder $callback
     * @return $this
     */
    public function crossJoin(string $table, Closure $callback): static
    {
        return $this->addJoinToStatement($callback(new JoinBuilder(JoinType::Cross, $table)));
    }

    /**
     * @param string $table
     * @param string $column1
     * @param string $column2
     * @return $this
     */
    public function crossJoinOn(string $table, string $column1, string $column2): static
    {
        return $this->addJoinToStatement((new JoinBuilder(JoinType::Cross, $table))->on($column1, $column2));
    }

    /**
     * @param string $table
     * @param Closure(JoinBuilder): JoinBuilder $callback
     * @return $this
     */
    public function leftJoin(string $table, Closure $callback): static
    {
        return $this->addJoinToStatement($callback(new JoinBuilder(JoinType::Left, $table)));
    }

    /**
     * @param string $table
     * @param string $column1
     * @param string $column2
     * @return $this
     */
    public function leftJoinOn(string $table, string $column1, string $column2): static
    {
        return $this->addJoinToStatement((new JoinBuilder(JoinType::Left, $table))->on($column1, $column2));
    }

    /**
     * @param string $table
     * @param Closure(JoinBuilder): JoinBuilder $callback
     * @return $this
     */
    public function rightJoin(string $table, Closure $callback): static
    {
        return $this->addJoinToStatement($callback(new JoinBuilder(JoinType::Right, $table)));
    }

    /**
     * @param string $table
     * @param string $column1
     * @param string $column2
     * @return $this
     */
    public function rightJoinOn(string $table, string $column1, string $column2): static
    {
        return $this->addJoinToStatement((new JoinBuilder(JoinType::Right, $table))->on($column1, $column2));
    }

    /**
     * @param string $table
     * @param Closure(JoinBuilder): JoinBuilder $callback
     * @return $this
     */
    public function fullJoin(string $table, Closure $callback): static
    {
        return $this->addJoinToStatement($callback(new JoinBuilder(JoinType::Full, $table)));
    }

    /**
     * @param string $table
     * @param string $column1
     * @param string $column2
     * @return $this
     */
    public function fullJoinOn(string $table, string $column1, string $column2): static
    {
        return $this->addJoinToStatement((new JoinBuilder(JoinType::Full, $table))->on($column1, $column2));
    }

    /**
     * @param JoinBuilder $builder
     * @return $this
     */
    protected function addJoinToStatement(JoinBuilder $builder): static
    {
        $this->statement->joins ??= [];
        $this->statement->joins[] = $builder->getDefinition();
        return $this;
    }

    #endregion join ---------------------------------------------------------------------------------------------------

    #region grouping ---------------------------------------------------------------------------------------------------

    /**
     * @param string ...$columns
     * @return $this
     */
    public function groupBy(string ...$columns): static
    {
        $this->statement->groupBy = $columns;
        return $this;
    }

    /**
     * @param string|ConditionBuilder $column
     * @param mixed $operator
     * @param mixed|null $value
     * @return $this
     */
    public function having(ConditionBuilder|string $column, mixed $operator, mixed $value = null): static
    {
        $this->addHavingCondition($this->buildCondition(...func_get_args())->getDefinition());
        return $this;
    }

    /**
     * @param ConditionDefinition $condition
     * @return $this
     */
    protected function addHavingCondition(ConditionDefinition $condition): static
    {
        $statement = $this->statement;
        $statement->having ??= [];
        $statement->having[] = $condition;
        return $this;
    }

    #endregion grouping ------------------------------------------------------------------------------------------------

    #region limiting ---------------------------------------------------------------------------------------------------

    /**
     * @param int $skipRows
     * @return $this
     */
    public function offset(int $skipRows): static
    {
        $this->statement->offset = $skipRows;
        return $this;
    }

    #endregion limiting ------------------------------------------------------------------------------------------------

    #region execution --------------------------------------------------------------------------------------------------

    /**
     * @return string
     */
    public function prepare(): string
    {
        return $this->getQueryFormatter()->formatSelectStatement($this->statement);
    }

    /**
     * @return array<mixed>
     */
    public function getBindings(): array
    {
        return $this->getQueryFormatter()->formatBindingsForSelect($this->statement);
    }

    /**
     * @return bool
     */
    public function exists(): bool
    {
        return $this->copy()->columns("1")->limit(1)->execute()->isNotEmpty();
    }

    /**
     * @return array<int>|int
     */
    public function count(): array|int
    {
        $statement = $this->statement;

        // If GROUP BY exists but no SELECT is defined, use the first GROUP BY column that was defined.
        if ($statement->columns === null && is_array($statement->groupBy)) {
            $this->addToSelect($statement->groupBy[0]);
        }

        $results = $this->copy()->addToSelect(new Aggregate('count', '*', 'total'))->execute();

        // when GROUP BY is defined, return in [columnValue => count] format
        if (is_array($statement->groupBy)) {
            $keyName = $statement->groupBy[0];
            $aggregated = [];
            foreach ($results as $result) {
                $groupKey = $result[$keyName];
                $groupTotal = (int) $result['total'];
                $aggregated[$groupKey] = $groupTotal;
            }
            return $aggregated;
        }

        if ($results->isEmpty()) {
            return 0;
        }

        return (int) $results->first()['total'];
    }

    /**
     * @param string $column
     * @return int|float
     */
    public function sum(string $column): float|int
    {
        return $this->aggregate($column, 'SUM');
    }

    /**
     * @param string $column
     * @return int|float
     */
    public function avg(string $column): float|int
    {
        return $this->aggregate($column, 'AVG');
    }

    /**
     * @param string $column
     * @return int
     */
    public function min(string $column): int
    {
        return $this->aggregate($column, 'MIN');
    }

    /**
     * @param string $column
     * @return int
     */
    public function max(string $column): int
    {
        return $this->aggregate($column, 'MAX');
    }

    /**
     * @param string $function
     * @param string $column
     * @return int
     */
    protected function aggregate(string $function, string $column): int
    {
        $alias = 'aggregate';
        $aggregate = new Aggregate($function, $column, $alias);
        return $this->copy()->columns($aggregate)->execute()->first()[$alias];
    }

    #endregion execution -----------------------------------------------------------------------------------------------
}
