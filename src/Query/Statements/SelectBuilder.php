<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Closure;
use Generator;
use Kirameki\Collections\Vec;
use Kirameki\Core\Exceptions\InvalidArgumentException;
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
 * @extends WhereBuilder<SelectStatement>
 */
class SelectBuilder extends WhereBuilder
{
    use ResultHelpers;

    /**
     * @var ConditionContext|null
     */
    protected ?ConditionContext $havingContext = null;

    /**
     * @param QueryHandler $handler
     */
    public function __construct(QueryHandler $handler)
    {
        parent::__construct($handler, new SelectStatement());
    }

    public function __clone()
    {
        parent::__clone();

        if ($this->statement->having !== null) {
            $this->statement->having = clone $this->statement->having;
        }
    }

    #region selecting --------------------------------------------------------------------------------------------------

    /**
     * @param string|Expression ...$columns
     * @return $this
     */
    public function __invoke(string|Expression ...$columns): static
    {
        return $this->columns(...$columns);
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
     * @param string|Expression ...$tables
     * @return $this
     */
    public function from(string|Expression ...$tables): static
    {
        $this->statement->tables = array_is_list($tables) ? $tables : array_values($tables);
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
     * @param Closure(JoinBuilder): mixed $callback
     * @return $this
     */
    public function join(string $table, Closure $callback): static
    {
        $builder = new JoinBuilder(JoinType::Inner, $table);
        $callback($builder);
        return $this->addJoinToStatement($builder);
    }

    /**
     * @param string $table
     * @param string $column1
     * @param string $column2
     * @return $this
     */
    public function joinOn(string $table, string $column1, string $column2): static
    {
        return $this->addJoinToStatement(new JoinBuilder(JoinType::Inner, $table)->on($column1, $column2));
    }

    /**
     * @param string $table
     * @param Closure(JoinBuilder): mixed $callback
     * @return $this
     */
    public function crossJoin(string $table, Closure $callback): static
    {
        $builder = new JoinBuilder(JoinType::Cross, $table);
        $callback($builder);
        return $this->addJoinToStatement($builder);
    }

    /**
     * @param string $table
     * @param string $column1
     * @param string $column2
     * @return $this
     */
    public function crossJoinOn(string $table, string $column1, string $column2): static
    {
        return $this->addJoinToStatement(new JoinBuilder(JoinType::Cross, $table)->on($column1, $column2));
    }

    /**
     * @param string $table
     * @param Closure(JoinBuilder): mixed $callback
     * @return $this
     */
    public function leftJoin(string $table, Closure $callback): static
    {
        $builder = new JoinBuilder(JoinType::Left, $table);
        $callback($builder);
        return $this->addJoinToStatement($builder);
    }

    /**
     * @param string $table
     * @param string $column1
     * @param string $column2
     * @return $this
     */
    public function leftJoinOn(string $table, string $column1, string $column2): static
    {
        return $this->addJoinToStatement(new JoinBuilder(JoinType::Left, $table)->on($column1, $column2));
    }

    /**
     * @param string $table
     * @param Closure(JoinBuilder): mixed $callback
     * @return $this
     */
    public function rightJoin(string $table, Closure $callback): static
    {
        $builder = new JoinBuilder(JoinType::Right, $table);
        $callback($builder);
        return $this->addJoinToStatement($builder);
    }

    /**
     * @param string $table
     * @param string $column1
     * @param string $column2
     * @return $this
     */
    public function rightJoinOn(string $table, string $column1, string $column2): static
    {
        return $this->addJoinToStatement(new JoinBuilder(JoinType::Right, $table)->on($column1, $column2));
    }

    /**
     * @param string $table
     * @param Closure(JoinBuilder): JoinBuilder $callback
     * @return $this
     */
    public function fullJoin(string $table, Closure $callback): static
    {
        $builder = new JoinBuilder(JoinType::Full, $table);
        $callback($builder);
        return $this->addJoinToStatement($builder);
    }

    /**
     * @param string $table
     * @param string $column1
     * @param string $column2
     * @return $this
     */
    public function fullJoinOn(string $table, string $column1, string $column2): static
    {
        return $this->addJoinToStatement(new JoinBuilder(JoinType::Full, $table)->on($column1, $column2));
    }

    /**
     * @param JoinBuilder $builder
     * @return $this
     */
    protected function addJoinToStatement(JoinBuilder $builder): static
    {
        $this->statement->joins ??= [];
        $this->statement->joins[] = $builder->join;
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
     * @param mixed ...$args
     * @return $this
     * @see WhereBuilder::where()
     */
    public function having(mixed ...$args): static
    {
        $context = $this->getHavingContext();
        $this->applyCondition($context, Logic::And, $args);
        $this->statement->having = $context->root;
        return $this;
    }

    /**
     * @return ConditionContext
     */
    protected function getHavingContext(): ConditionContext
    {
        return $this->havingContext ??= new ConditionContext();
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
        return new CompoundBuilder($this->handler, $operator, $this->statement, clone $query->statement);
    }

    #endregion compounding ---------------------------------------------------------------------------------------------

    #region execution --------------------------------------------------------------------------------------------------

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
     * @return OffsetPaginator<object>
     */
    public function offsetPaginate(int $page, int $size = Paginator::DEFAULT_PER_PAGE): OffsetPaginator
    {
        if ($page <= 0) {
            throw new InvalidArgumentException("Invalid page number. Expected: > 0. Got: {$page}.", [
                'statement' => $this->statement,
                'page' => $page,
                'size' => $size,
            ]);
        }

        if ($size <= 0) {
            throw new InvalidArgumentException("Invalid page size. Expected: > 0. Got: {$size}.", [
                'statement' => $this->statement,
                'page' => $page,
                'size' => $size,
            ]);
        }

        $total = $this->count();
        $result = $this->copy()->offset(($page - 1) * $size)->limit($size)->execute();
        return new OffsetPaginator($result, $size, $page, $total);
    }

    /**
     * @param int $size
     * @param Cursor|null $cursor
     * @return CursorPaginator<object>
     */
    public function cursorPaginate(int $size = Paginator::DEFAULT_PER_PAGE, ?Cursor $cursor = null): CursorPaginator
    {
        if ($size <= 0) {
            throw new InvalidArgumentException("Invalid page size. Expected: > 0. Got: {$size}.", [
                'statement' => $this->statement,
                'size' => $size,
                'cursor' => $cursor,
            ]);
        }

        $query = $this->copy()->limit($size + 1);
        $cursor?->applyTo($query);
        $result = $query->execute();

        $rows = $result->takeFirst($size);
        $next = $result->atOrNull($size);
        $cursor ??= Cursor::init($this, $next);
        $hasNext = $next !== null;

        return new CursorPaginator($rows, $size, $cursor, $hasNext);
    }

    /**
     * @param string $column
     * @return Vec<mixed>
     */
    public function pluck(string $column): Vec
    {
        return $this->copy()->columns($column)->execute()->map(static fn($row) => $row->$column ?? null);
    }

    /**
     * @return bool
     */
    public function exists(): bool
    {
        return $this->count() > 0;
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
            throw new LogicException('Cannot tally without a GROUP BY clause. Use count instead.', [
                'statement' => $this->statement,
            ]);
        }

        // If GROUP BY exists but no SELECT is defined, use the first GROUP BY column that was defined.
        if ($statement->columns === []) {
            $this->addToSelect($statement->groupBy[0]);
        }

        $results = $this->copy()
            ->addToSelect(new Count(as: 'total'))
            ->execute();

        // when GROUP BY is defined, return in [columnValue => count] format
        $keyName = $statement->groupBy[0];
        $aggregated = [];
        foreach ($results as $result) {
            $groupKey = $result->$keyName;
            $groupTotal = (int) $result->total;
            $aggregated[$groupKey] = $groupTotal;
        }
        return $aggregated;
    }

    /**
     * @param string $column
     * @return int|float
     */
    public function sum(string $column): float|int
    {
        return $this->copy()->columns(new Sum($column))->value(Sum::$defaultAlias) + 0;
    }

    /**
     * @param string $column
     * @return float
     */
    public function avg(string $column): float
    {
        return (float) $this->copy()->columns(new Avg($column))->value(Avg::$defaultAlias);
    }

    /**
     * @param string $column
     * @return int
     */
    public function min(string $column): int
    {
        return $this->copy()->columns(new Min($column))->value(Min::$defaultAlias);
    }

    /**
     * @param string $column
     * @return int
     */
    public function max(string $column): int
    {
        return $this->copy()->columns(new Max($column))->value(Max::$defaultAlias);
    }

    #endregion execution -----------------------------------------------------------------------------------------------

    #region batching ---------------------------------------------------------------------------------------------------

    /**
     * @param int $size
     * @return Generator<CursorPaginator<object>>
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

            $cursor = $paginator->generateNextCursorOrNull();
        } while ($cursor !== null);
    }

    /**
     * @return Generator<mixed>
     */
    public function flatBatch(int $chunkSize = 1_000): Generator
    {
        foreach ($this->batch($chunkSize) as $paginator) {
            foreach ($paginator as $row) {
                yield $row;
            }
        }
    }

    #endregion batching ------------------------------------------------------------------------------------------------
}
