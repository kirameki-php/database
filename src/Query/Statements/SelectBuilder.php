<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Closure;
use Generator;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\Expression;
use Kirameki\Database\Query\Expressions\Avg;
use Kirameki\Database\Query\Expressions\Count;
use Kirameki\Database\Query\Expressions\Max;
use Kirameki\Database\Query\Expressions\Min;
use Kirameki\Database\Query\Expressions\Sum;
use Kirameki\Database\Query\Pagination\Cursor;
use Kirameki\Database\Query\Pagination\CursorPaginator;
use Kirameki\Database\Query\Pagination\OffsetPaginator;
use Kirameki\Database\Query\Pagination\Paginator;
use Kirameki\Database\Query\QueryHandler;
use Kirameki\Database\Query\QueryResult;
use function array_is_list;
use function array_values;
use function is_array;
use function min;

/**
 * @extends ConditionsBuilder<SelectStatement>
 */
class SelectBuilder extends ConditionsBuilder
{
    /**
     * @param QueryHandler $handler
     */
    public function __construct(QueryHandler $handler)
    {
        parent::__construct($handler, new SelectStatement());
    }

    #region selecting --------------------------------------------------------------------------------------------------

    /**
     * @param string|Expression ...$tables
     * @return $this
     */
    public function from(string|Expression ...$tables): static
    {
        $this->statement->tables = array_is_list($tables) ? $tables : array_values($tables);
        return $this;
    }

    /**
     * @param string|Expression ...$columns
     * @return $this
     */
    public function columns(string|Expression ...$columns): static
    {
        $this->statement->columns = array_is_list($columns) ? $columns : array_values($columns);
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
     * @param string $name
     * @return $this
     */
    public function forceIndex(string $name): static
    {
        $this->statement->forceIndex = $name;
        return $this;
    }

    /**
     * @param string|Expression $column
     * @return $this
     */
    protected function addToSelect(string|Expression $column): static
    {
        $this->statement->columns[] = $column;
        return $this;
    }

    #endregion selecting -----------------------------------------------------------------------------------------------

    #region locking ----------------------------------------------------------------------------------------------------

    /**
     * @return $this
     */
    public function forShare(): static
    {
        $this->statement->lock = new Lock(LockType::Shared, null);
        return $this;
    }

    /**
     * @param LockOption|null $option
     * @return $this
     */
    public function forUpdate(?LockOption $option = null): static
    {
        $this->statement->lock = new Lock(LockType::Exclusive, $option);
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
        $this->statement->groupBy = array_is_list($columns) ? $columns : array_values($columns);
        return $this;
    }

    /**
     * @see ConditionsBuilder::where()
     * @param mixed ...$args
     * @return $this
     */
    public function having(mixed ...$args): static
    {
        $statement = $this->statement;
        $statement->having ??= [];
        $statement->having[] = $this->buildCondition(...$args)->getDefinition();
        return $this;
    }

    #endregion grouping ------------------------------------------------------------------------------------------------

    #region sorting ----------------------------------------------------------------------------------------------------

    /**
     * @param string $column
     * @param SortOrder $sort
     * @param NullOrder|null $nulls
     * @return $this
     */
    public function orderBy(
        string $column,
        SortOrder $sort = SortOrder::Ascending,
        ?NullOrder $nulls = null,
    ): static
    {
        $this->statement->orderBy ??= [];
        $this->statement->orderBy[$column] = new Ordering($sort, $nulls);
        return $this;
    }

    /**
     * @param string $column
     * @param NullOrder|null $nulls
     * @return $this
     */
    public function orderByAsc(string $column, ?NullOrder $nulls = null): static
    {
        return $this->orderBy($column, SortOrder::Ascending, $nulls);
    }

    /**
     * @param string $column
     * @param NullOrder|null $nulls
     * @return $this
     */
    public function orderByDesc(string $column, ?NullOrder $nulls = null): static
    {
        return $this->orderBy($column, SortOrder::Descending, $nulls);
    }

    /**
     * @return $this
     */
    public function reorder(): static
    {
        $this->statement->orderBy = null;
        return $this;
    }

    #endregion sorting -------------------------------------------------------------------------------------------------

    #region limiting ---------------------------------------------------------------------------------------------------

    /**
     * @param int $offset
     * @return $this
     */
    public function offset(int $offset): static
    {
        $this->statement->offset = $offset;
        return $this;
    }

    /**
     * @param int $count
     * @return $this
     */
    public function limit(int $count): static
    {
        $this->statement->limit = $count;
        return $this;
    }

    #endregion limiting ------------------------------------------------------------------------------------------------

    #region compounding ------------------------------------------------------------------------------------------------

    /**
     * @param SelectBuilder $query
     * @return CompoundBuilder
     */
    public function union(SelectBuilder $query): CompoundBuilder
    {
        return $this->setCompoundOperator(CompoundType::Union, $query);
    }

    /**
     * @param SelectBuilder $query
     * @return CompoundBuilder
     */
    public function unionAll(SelectBuilder $query): CompoundBuilder
    {
        return $this->setCompoundOperator(CompoundType::UnionAll, $query);
    }

    /**
     * @param SelectBuilder $query
     * @return CompoundBuilder
     */
    public function intersect(SelectBuilder $query): CompoundBuilder
    {
        return $this->setCompoundOperator(CompoundType::Intersect, $query);
    }

    /**
     * @param SelectBuilder $query
     * @return CompoundBuilder
     */
    public function except(SelectBuilder $query): CompoundBuilder
    {
        return $this->setCompoundOperator(CompoundType::Except, $query);
    }

    /**
     * @param CompoundType $operator
     * @param SelectBuilder $query
     * @return CompoundBuilder
     */
    protected function setCompoundOperator(CompoundType $operator, SelectBuilder $query): CompoundBuilder
    {
        return new CompoundBuilder($this->handler, $operator, $this->statement, $query->getStatement());
    }

    #endregion compounding ---------------------------------------------------------------------------------------------

    #region execution --------------------------------------------------------------------------------------------------

    /**
     * @return QueryResult<SelectStatement, mixed>
     */
    public function cursor(): QueryResult
    {
        return $this->handler->cursor($this->statement);
    }

    /**
     * @param int $size
     * @return QueryResult<SelectStatement, mixed>
     */
    public function exactly(int $size): QueryResult
    {
        return $this->copy()->limit($size)->execute()->ensureCountIs($size);
    }

    /**
     * @param int $page
     * @param int $size
     * @return OffsetPaginator<mixed>
     */
    public function offsetPaginate(int $page, int $size = Paginator::DEFAULT_PAGE_SIZE): OffsetPaginator
    {
        $total = $this->copy()->count();
        $result = $this->copy()->offset(($page - 1) * $size)->limit($size)->execute();
        return new OffsetPaginator($result, $size, $page, $total);
    }

    /**
     * @param int $size
     * @param Cursor|null $cursor
     * @return CursorPaginator<mixed>
     */
    public function cursorPaginate(int $size = Paginator::DEFAULT_PAGE_SIZE, ?Cursor $cursor = null): CursorPaginator
    {
        $cursor?->apply($this);
        $cursor ??= Cursor::init($this, $size);

        $result = $this->copy()->limit($size + 1)->execute();
        return new CursorPaginator($result, $cursor);
    }

    /**
     * @return mixed
     */
    public function first(): mixed
    {
        return $this->copy()->limit(1)->execute()->first();
    }

    /**
     * @return mixed
     */
    public function firstOrNull(): mixed
    {
        return $this->copy()->limit(1)->execute()->firstOrNull();
    }

    /**
     * @return mixed
     */
    public function single(): mixed
    {
        return $this->copy()->limit(2)->execute()->single();
    }

    /**
     * @param string $column
     * @return QueryResult<SelectStatement, mixed>
     */
    public function pluck(string $column): QueryResult
    {
        return $this->copy()->columns($column)->limit(1)->execute();
    }

    /**
     * @param string $column
     * @return mixed
     */
    public function value(string $column): mixed
    {
        $value = $this->valueOrNull($column);
        if ($value === null) {
            throw new LogicException("Expected query to return a row, but none was returned.", [
                'column' => $column,
                'statement' => $this->statement,
            ]);
        }
        return $value;
    }

    /**
     * @param string $column
     * @return mixed
     */
    public function valueOrNull(string $column): mixed
    {
        return $this->copy()->limit(1)->execute()->firstOrNull()?->{$column};
    }

    /**
     * @return bool
     */
    public function exists(): bool
    {
        return $this->copy()->columns('1')->limit(1)->execute()->isNotEmpty();
    }

    /**
     * @return int
     */
    public function count(): int
    {
        // when GROUP BY is defined, return in [columnValue => count] format
        if (is_array($this->statement->groupBy)) {
            throw new LogicException('Cannot get count when GROUP BY is defined. Use tally instead.', [
                'statement' => $this->statement,
            ]);
        }

        return $this->copy()
            ->addToSelect(new Count(as: 'total'))
            ->valueOrNull('total') ?? 0;
    }

    /**
     * @return array<int>
     */
    public function tally(): array
    {
        $statement = $this->statement;

        if ($statement->groupBy === null) {
            throw new LogicException('Cannot get total count when GROUP BY is not defined', [
                'statement' => $this->statement,
            ]);
        }

        // If GROUP BY exists but no SELECT is defined, use the first GROUP BY column that was defined.
        if ($statement->columns === null) {
            $this->addToSelect($statement->groupBy[0]);
        }

        $results = $this->copy()
            ->addToSelect(new Avg(as: 'total'))
            ->execute();

        // when GROUP BY is defined, return in [columnValue => count] format
        $keyName = $statement->groupBy[0];
        $aggregated = [];
        foreach ($results as $result) {
            $groupKey = $result[$keyName];
            $groupTotal = (int) $result['total'];
            $aggregated[$groupKey] = $groupTotal;
        }
        return $aggregated;
    }

    /**
     * @param string $column
     * @return int|float
     */
    public function sum(string $column = '*'): float|int
    {
        return $this->copy()->columns(new Sum($column))->value(Sum::$defaultAlias);
    }

    /**
     * @param string $column
     * @return int|float
     */
    public function avg(string $column = '*'): float|int
    {
        return $this->copy()->columns(new Avg($column))->value(Avg::$defaultAlias);
    }

    /**
     * @param string $column
     * @return int
     */
    public function min(string $column = '*'): int
    {
        return $this->copy()->columns(new Min($column))->value(Min::$defaultAlias);
    }

    /**
     * @param string $column
     * @return int
     */
    public function max(string $column = '*'): int
    {
        return $this->copy()->columns(new Max($column))->value(Max::$defaultAlias);
    }

    #endregion execution -----------------------------------------------------------------------------------------------

    #region batching ---------------------------------------------------------------------------------------------------

    /**
     * @param int $size
     * @return Generator<CursorPaginator<mixed>>
     */
    public function batch(int $size = 1_000): Generator
    {
        $cursor = null;
        $limit = $this->statement->limit;
        $this->statement->limit = null;

        do {
            $size = min($limit ?? $size, $size);
            $paginator = $this->cursorPaginate($size, $cursor);

            if ($paginator->isEmpty()) {
                break;
            }

            yield $paginator;

            if ($limit !== null) {
                $limit -= $paginator->count();
                if ($limit <= 0) {
                    break;
                }
            }

            $cursor = $paginator->getnextCursor();
        } while ($paginator->hasMorePages());
    }

    /**
     * @return Generator<mixed>
     */
    public function batchEach(int $chunkSize = 1_000): Generator
    {
        foreach ($this->batch($chunkSize) as $paginator) {
            foreach ($paginator as $row) {
                yield $row;
            }
        }
    }

    #endregion batching ------------------------------------------------------------------------------------------------
}
