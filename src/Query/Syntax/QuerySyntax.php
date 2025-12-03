<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Syntax;

use Kirameki\Collections\Utils\Arr;
use Kirameki\Exceptions\InvalidArgumentException;
use Kirameki\Exceptions\LogicException;
use Kirameki\Exceptions\NotSupportedException;
use Kirameki\Exceptions\UnreachableException;
use Kirameki\Core\Func;
use Kirameki\Database\Expression;
use Kirameki\Database\Info\Statements\ListColumnsStatement;
use Kirameki\Database\Info\Statements\ListForeignKeysStatement;
use Kirameki\Database\Info\Statements\ListIndexesStatement;
use Kirameki\Database\Info\Statements\ListTablesStatement;
use Kirameki\Database\Info\Statements\TableExistsStatement;
use Kirameki\Database\Query\Expressions\QueryFunction;
use Kirameki\Database\Query\Expressions\WindowDefinition;
use Kirameki\Database\Query\Statements\Bounds;
use Kirameki\Database\Query\Statements\CheckingCondition;
use Kirameki\Database\Query\Statements\ComparingCondition;
use Kirameki\Database\Query\Statements\Compound;
use Kirameki\Database\Query\Statements\Condition;
use Kirameki\Database\Query\Statements\ConditionStatement;
use Kirameki\Database\Query\Statements\Cte;
use Kirameki\Database\Query\Statements\Dataset;
use Kirameki\Database\Query\Statements\DeleteStatement;
use Kirameki\Database\Query\Statements\InsertStatement;
use Kirameki\Database\Query\Statements\JoinDefinition;
use Kirameki\Database\Query\Statements\Lock;
use Kirameki\Database\Query\Statements\LockOption;
use Kirameki\Database\Query\Statements\LockType;
use Kirameki\Database\Query\Statements\Logic;
use Kirameki\Database\Query\Statements\NestedCondition;
use Kirameki\Database\Query\Statements\Operator;
use Kirameki\Database\Query\Statements\Ordering;
use Kirameki\Database\Query\Statements\QueryStatement;
use Kirameki\Database\Query\Statements\RawCondition;
use Kirameki\Database\Query\Statements\RawStatement;
use Kirameki\Database\Query\Statements\SelectStatement;
use Kirameki\Database\Query\Statements\SortOrder;
use Kirameki\Database\Query\Statements\Tags;
use Kirameki\Database\Query\Statements\TagsFormat;
use Kirameki\Database\Query\Statements\UpdateStatement;
use Kirameki\Database\Query\Statements\UpsertStatement;
use Kirameki\Database\Syntax;
use stdClass;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_push;
use function count;
use function current;
use function explode;
use function get_debug_type;
use function implode;
use function is_bool;
use function is_countable;
use function is_iterable;
use function is_string;
use function iterator_to_array;
use function next;
use function preg_match;
use function preg_quote;
use function preg_replace_callback;
use function rawurlencode;
use function str_contains;

abstract class QuerySyntax extends Syntax
{
    /**
     * FOR DEBUGGING ONLY
     *
     * @param string $template
     * @param list<mixed> $parameters
     * @return string
     */
    public function interpolate(string $template, array $parameters): string
    {
        $parameters = $this->normalizeParameters($parameters);
        $parameters = Arr::flatten($parameters);

        // null values will be turned into IS [NOT] NULL and will be part of the template so
        // they will not be included in the parameters
        $parameters = array_filter($parameters, Func::notNull());

        $remains = count($parameters);

        $interpolated = (string) preg_replace_callback('/\?\??/', function($matches) use (&$parameters, &$remains) {
            if ($matches[0] === '??') {
                return '??';
            }
            $value = current($parameters);
            next($parameters);
            $remains--;
            return match (true) {
                is_bool($value) => $value ? 'TRUE' : 'FALSE',
                is_string($value) => $this->asLiteral($value),
                default => (string) $value,
            };
        }, $template);

        if ($remains !== 0) {
            throw new LogicException("Invalid number of parameters given for query. (query: {$template}, remains: {$remains})", [
                'template' => $template,
                'parameters' => $parameters,
                'remains' => $remains,
            ]);
        }

        return $interpolated;
    }

    /**
     * @param SelectStatement $statement
     * @return string
     */
    public function prepareTemplateForSelect(SelectStatement $statement): string
    {
        $query = $this->concat([
            $this->formatSelectPart($statement),
            $this->formatFromPart($statement),
            $this->formatJoinPart($statement),
            $this->formatWherePart($statement),
            $this->formatGroupByPart($statement),
            $this->formatHavingPart($statement),
            $this->formatOrderByPart($statement->orderBy),
            $this->formatLimitPart($statement->limit),
            $this->formatOffsetPart($statement->offset),
            $this->formatSelectLockPart($statement->lock),
        ]);

        return $this->concat([
            $this->formatWithPart($statement),
            $this->formatCompoundPart($query, $statement->compound),
            $this->formatTags($statement->tags),
        ]);
    }

    /**
     * @param SelectStatement $statement
     * @return list<mixed>
     */
    public function prepareParametersForSelect(SelectStatement $statement): array
    {
        $parameters = [];
        $this->addParametersForSelect($parameters, $statement);
        return $this->normalizeParameters($parameters);
    }

    /**
     * @param list<mixed> $parameters
     * @param SelectStatement $statement
     */
    protected function addParametersForSelect(array &$parameters, SelectStatement $statement): void
    {
        $this->addParametersForCte($parameters, $statement);
        $this->addParametersForJoins($parameters, $statement);
        $this->addParametersForWhere($parameters, $statement);
        $this->addParametersForHaving($parameters, $statement);
        $this->addParametersForCompound($parameters, $statement);
    }

    /**
     * @param InsertStatement $statement
     * @return string
     */
    public function prepareTemplateForInsert(InsertStatement $statement): string
    {
        $columns = $statement->dataset->getColumns();

        if ($columns === []) {
            return "INSERT INTO {$this->asIdentifier($statement->table)} DEFAULT VALUES";
        }

        return 'INSERT INTO ' . $this->concat([
            $this->asIdentifier($statement->table),
            $this->formatDatasetColumnsPart($columns),
            'VALUES',
            $this->formatInsertDatasetValuesPart($statement->dataset, $columns),
            $this->formatReturningPart($statement->returning),
            $this->formatTags($statement->tags),
        ]);
    }

    /**
     * @param InsertStatement $statement
     * @return list<mixed>
     */
    public function prepareParametersForInsert(InsertStatement $statement): array
    {
        $parameters = [];
        $this->addParametersForInsert($parameters, $statement);
        return $this->normalizeParameters($parameters);
    }

    /**
     * @param list<mixed> $parameters
     * @param InsertStatement $statement
     */
    protected function addParametersForInsert(array &$parameters, InsertStatement $statement): void
    {
        $this->addParametersForCte($parameters, $statement);
        $this->addParametersForDataset($parameters, $statement->dataset);
    }

    /**
     * @param UpsertStatement $statement
     * @return string
     */
    public function prepareTemplateForUpsert(UpsertStatement $statement): string
    {
        $columns = $statement->dataset->getColumns();

        return 'INSERT INTO ' . $this->concat([
            $this->asIdentifier($statement->table),
            $this->formatDatasetColumnsPart($columns),
            'VALUES',
            $this->formatUpsertDatasetValuesPart($statement->dataset, $columns),
            $this->formatUpsertOnConflictPart($statement->onConflict),
            $this->formatUpsertUpdateSet($columns),
            $this->formatReturningPart($statement->returning),
            $this->formatTags($statement->tags),
        ]);
    }

    /**
     * @param UpsertStatement $statement
     * @return list<mixed>
     */
    public function prepareParametersForUpsert(UpsertStatement $statement): array
    {
        $parameters = [];
        $this->addParametersForCte($parameters, $statement);
        $this->addParametersForUpsert($parameters, $statement);
        return $this->normalizeParameters($parameters);
    }

    /**
     * @param list<mixed> $parameters
     * @param UpsertStatement $statement
     */
    protected function addParametersForUpsert(array &$parameters, UpsertStatement $statement): void
    {
        $this->addParametersForDataset($parameters, $statement->dataset);
    }

    /**
     * @param UpdateStatement $statement
     * @return string
     */
    public function prepareTemplateForUpdate(UpdateStatement $statement): string
    {
        return $this->concat([
            $this->formatWithPart($statement),
            'UPDATE',
            $this->asIdentifier($statement->table),
            'SET',
            $this->formatUpdateAssignmentsPart($statement),
            $this->formatWherePart($statement),
            $this->formatReturningPart($statement->returning),
            $this->formatTags($statement->tags),
        ]);
    }

    /**
     * @param UpdateStatement $statement
     * @return list<mixed>
     */
    public function prepareParametersForUpdate(UpdateStatement $statement): array
    {
        $parameters = [];
        $this->addParametersForUpdate($parameters, $statement);
        return $this->normalizeParameters($parameters);
    }

    /**
     * @param list<mixed> $parameters
     * @param UpdateStatement $statement
     */
    protected function addParametersForUpdate(array &$parameters, UpdateStatement $statement): void
    {
        $this->addParametersForCte($parameters, $statement);

        $set = $statement->set ?? throw new LogicException('No values to update', ['statement' => $statement]);
        foreach ($set as $value) {
            $parameters[] = $value;
        }

        $this->addParametersForWhere($parameters, $statement);
    }

    /**
     * @param DeleteStatement $statement
     * @return string
     */
    public function prepareTemplateForDelete(DeleteStatement $statement): string
    {
        return $this->concat([
            $this->formatWithPart($statement),
            'DELETE FROM',
            $this->asIdentifier($statement->table),
            $this->formatWherePart($statement),
            $this->formatReturningPart($statement->returning),
            $this->formatTags($statement->tags),
        ]);
    }

    /**
     * @param DeleteStatement $statement
     * @return list<mixed>
     */
    public function prepareParametersForDelete(DeleteStatement $statement): array
    {
        $parameters = [];
        $this->addParametersForDelete($parameters, $statement);
        return $this->normalizeParameters($parameters);
    }

    /**
     * @param list<mixed> $parameters
     * @param DeleteStatement $statement
     */
    protected function addParametersForDelete(array &$parameters, DeleteStatement $statement): void
    {
        $this->addParametersForCte($parameters, $statement);
        $this->addParametersForWhere($parameters, $statement);
    }

    /**
     * @param RawStatement $statement
     * @return string
     */
    public function prepareTemplateForRaw(RawStatement $statement): string
    {
        return $this->concat([
            $statement->template,
            $this->formatTags($statement->tags),
        ]);
    }

    /**
     * @param RawStatement $statement
     * @return list<mixed>
     */
    public function prepareParametersForRaw(RawStatement $statement): array
    {
        $parameters = [];
        $this->addParametersForRaw($parameters, $statement);
        return $this->normalizeParameters($parameters);
    }

    /**
     * @param list<mixed> $parameters
     * @param RawStatement $statement
     */
    protected function addParametersForRaw(array &$parameters, RawStatement $statement): void
    {
        $this->addParametersForCte($parameters, $statement);
        foreach ($statement->parameters as $value) {
            $parameters[] = $value;
        }
    }

    /**
     * @param ConditionStatement $statement
     * @return string
     */
    protected function formatWithPart(ConditionStatement $statement): string
    {
        $with = $statement->with;

        if ($with === null) {
            return '';
        }

        return $this->concat([
            'WITH',
            $with->recursive ? 'RECURSIVE' : null,
            $this->asCsv(array_map($this->formatCte(...), $with->items)),
        ]);
    }

    /**
     * @param Cte $with
     * @return string
     */
    protected function formatCte(Cte $with): string
    {
        return $this->concat([
            $this->asIdentifier($with->name),
            count($with->columns) > 0
                ? $this->asEnclosedCsv($this->asColumns($with->columns))
                : null,
            'AS',
            $this->formatSubQuery($with->as),
        ]);
    }

    /**
     * @param SelectStatement $statement
     * @return string
     */
    protected function formatSelectPart(SelectStatement $statement): string
    {
        return 'SELECT ' . $this->concat([
            $statement->distinct ? 'DISTINCT' : null,
            $this->formatSelectColumnsPart($statement),
        ]);
    }

    /**
     * @param SelectStatement $statement
     * @return string
     */
    protected function formatSelectColumnsPart(SelectStatement $statement): string
    {
        $columns = $statement->columns;

        if ($columns === null || count($columns) === 0) {
            return '*';
        }

        return $this->asCsv($this->asColumns($columns, true));
    }

    /**
     * @param Lock|null $lock
     * @return string
     */
    protected function formatSelectLockPart(?Lock $lock): string
    {
        $type = $lock?->type;

        return match ($type) {
            LockType::Exclusive => $type->value . $this->formatSelectLockOptionPart($lock?->option),
            LockType::Shared => $type->value,
            null => '',
        };
    }

    /**
     * @param LockOption|null $option
     * @return string
     */
    protected function formatSelectLockOptionPart(?LockOption $option): string
    {
        return $option !== null ? ' ' . $option->value : '';
    }

    /**
     * @param SelectStatement $statement
     * @return string
     */
    protected function formatFromPart(SelectStatement $statement): string
    {
        $expressions = [];
        foreach ($statement->tables ?? [] as $table) {
            $expressions[] = ($table instanceof Expression)
                ? $table->toValue($this)
                : $this->asTable($table);
        }
        if (count($expressions) === 0) {
            return '';
        }
        return 'FROM ' . $this->concat([
            $this->asCsv($expressions),
            $this->formatFromUseIndexPart($statement),
        ]);
    }

    /**
     * @param SelectStatement $statement
     * @return string
     */
    abstract protected function formatFromUseIndexPart(SelectStatement $statement): string;

    /**
     * @param SelectStatement $statement
     * @return string
     */
    protected function formatJoinPart(SelectStatement $statement): string
    {
        $joins = $statement->joins;

        if ($joins === null) {
            return '';
        }

        return implode(' ', array_map(function(JoinDefinition $def): string {
            $expr = $def->type->value . ' ';
            $expr .= $this->asTable($def->table);
            if ($def->using !== null) {
                $expr .= ' USING ' . $this->asEnclosedCsv($this->asColumns($def->using));
            }
            if ($def->condition !== null) {
                $expr .= ' ON ' . $this->formatCondition($def->condition);
            }
            return $expr;
        }, $joins));
    }

    /**
     * @param string $query
     * @param Compound|null $compound
     * @return string
     */
    protected function formatCompoundPart(string $query, ?Compound $compound): string
    {
        if ($compound === null) {
            return $query;
        }

        return $this->concat([
            $this->formatCompoundTemplate($query),
            $compound->operator->value,
            $this->formatCompoundTemplate($this->prepareTemplateForSelect($compound->query)),
            $this->formatOrderByPart($compound->orderBy),
            $this->formatLimitPart($compound->limit),
        ]);
    }

    protected function formatCompoundTemplate(string $query): string
    {
        return '(' . $query . ')';
    }

    /**
     * @param list<string> $columns
     * @return string
     */
    protected function formatDatasetColumnsPart(array $columns): string
    {
        return $this->asEnclosedCsv($this->asColumns($columns));
    }

    /**
     * @param Dataset $dataset
     * @param list<string> $columns
     * @return string
     */
    protected function formatInsertDatasetValuesPart(Dataset $dataset, array $columns): string
    {
        $placeholders = [];
        foreach ($dataset as $data) {
            $binders = [];
            foreach ($columns as $column) {
                if (array_key_exists($column, $data)) {
                    $value = $data[$column];
                    $binders[] = ($value instanceof Expression)
                        ? $value->toValue($this)
                        : '?';
                } else {
                    $binders[] = 'DEFAULT';
                }
            }
            $placeholders[] = $this->asEnclosedCsv($binders);
        }
        return $this->asCsv($placeholders);
    }

    /**
     * @param UpdateStatement $statement
     * @return string
     */
    protected function formatUpdateAssignmentsPart(UpdateStatement $statement): string
    {
        $set = $statement->set ?? throw new LogicException('No values to update', ['statement' => $statement]);
        $columns = array_keys($set);
        $assignments = array_map(fn(string $column): string => "{$this->asIdentifier($column)} = ?", $columns);
        return $this->asCsv($assignments);
    }

    /**
     * @param Dataset $dataset
     * @param list<string> $columns
     * @return string
     */
    protected function formatUpsertDatasetValuesPart(Dataset $dataset, array $columns): string
    {
        return $this->formatInsertDatasetValuesPart($dataset, $columns);
    }

    /**
     * @param list<string> $onConflict
     * @return string
     */
    protected function formatUpsertOnConflictPart(array $onConflict): string
    {
        $clause = 'ON CONFLICT';
        if (count($onConflict) === 0) {
            return $clause;
        }
        return $clause . ' ' . $this->asEnclosedCsv($this->asIdentifiers($onConflict));
    }

    /**
     * @param list<string> $columns
     * @return string
     */
    protected function formatUpsertUpdateSet(array $columns): string
    {
        $columns = $this->asIdentifiers($columns);
        $columns = array_map(static fn(string $column): string => "{$column} = EXCLUDED.{$column}", $columns);
        return 'DO UPDATE SET ' . $this->asCsv($columns);
    }

    /**
     * @param ConditionStatement $statement
     * @return string
     */
    protected function formatWherePart(ConditionStatement $statement): string
    {
        return $statement->where !== null
            ? 'WHERE ' . $this->formatCondition($statement->where)
            : '';
    }

    /**
     * @param Condition $def
     * @return string
     */
    protected function formatCondition(Condition $def): string
    {
        $parts = [];
        do {
            $part = match (true) {
                $def instanceof ComparingCondition => $this->formatComparingCondition($def),
                $def instanceof NestedCondition => $this->formatNestedCondition($def),
                $def instanceof CheckingCondition => $this->formatCheckingCondition($def),
                $def instanceof RawCondition => $this->formatRawCondition($def),
                // @codeCoverageIgnoreStart
                default => throw new UnreachableException(),
                // @codeCoverageIgnoreEnd
            };

            if ($def->logic !== null) {
                $part .= ' ' . $def->logic->value;
            }

            $parts[] = $part;
        } while ($def = $def->next);

        return implode(' ', $parts);
    }

    /**
     * @param ComparingCondition $def
     * @return string
     */
    protected function formatComparingCondition(ComparingCondition $def): string
    {
        return match ($def->operator) {
            Operator::Equals,
            Operator::NotEquals => $this->formatConditionForEquals($def),
            Operator::LessThan,
            Operator::LessThanOrEqualTo,
            Operator::GreaterThan,
            Operator::GreaterThanOrEqualTo,
            Operator::Like,
            Operator::NotLike => $this->formatConditionForOperator($def),
            Operator::In,
            Operator::NotIn => $this->formatConditionForIn($def),
            Operator::Between,
            Operator::NotBetween => $this->formatConditionForBetween($def),
            Operator::InRange,
            Operator::NotInRange => $this->formatConditionForRange($def),
        };
    }

    /**
     * @param NestedCondition $def
     * @return string
     */
    protected function formatNestedCondition(NestedCondition $def): string
    {
        return '(' . $this->formatCondition($def->value) . ')';
    }

    /**
     * @param RawCondition $def
     * @return string
     */
    protected function formatRawCondition(RawCondition $def): string
    {
        return $this->stringifyExpression($def->value);
    }

    /**
     * @param CheckingCondition $def
     * @return string
     */
    protected function formatCheckingCondition(CheckingCondition $def): string
    {
        $value = $def->value;
        $operator = $def->negated ? 'NOT EXISTS' : 'EXISTS';

        if ($value instanceof QueryStatement) {
            return "{$operator} {$this->formatSubQuery($value)}";
        }

        $message = 'Value for WHERE ' . $operator . '. ';
        $message .= 'Expected: SelectStatement. Got: ' . get_debug_type($value) . '.';
        throw new NotSupportedException($message, [
            'definition' => $def,
        ]);
    }

    /**
     * @param ComparingCondition $def
     * @return string
     */
    protected function formatConditionForEquals(ComparingCondition $def): string
    {
        return $def->value !== null
            ? $this->formatConditionForOperator($def)
            : $this->formatConditionForNull($def);
    }

    /**
     * @param ComparingCondition $def
     * @return string
     */
    protected function formatConditionForIn(ComparingCondition $def): string
    {
        $column = $this->asColumn($def->column);
        $operator = $def->operator->value;
        $value = $def->value;

        if (is_iterable($value)) {
            $placeholders = [];
            $containsNull = false;
            foreach ($value as $v) {
                if ($v !== null) {
                    $placeholders[] = $this->asPlaceholder($v);
                } else {
                    $containsNull = true;
                }
            }
            if (count($placeholders) === 0) {
                $placeholders[] = 'NULL';
            }

            $sql = "{$column} {$operator} {$this->asEnclosedCsv($placeholders)}";

            return $containsNull
                ? "({$sql} OR {$column} IS NULL)"
                : $sql;
        }

        if ($value instanceof QueryStatement) {
            return "{$column} {$operator} {$this->formatSubQuery($value)}";
        }

        $message = 'Value for WHERE ' . $operator . '. ';
        $message .= 'Expected: iterable|SelectStatement. Got: ' . get_debug_type($value) . '.';
        throw new NotSupportedException($message, [
            'definition' => $def,
        ]);
    }

    /**
     * @param ComparingCondition $def
     * @return string
     */
    protected function formatConditionForBetween(ComparingCondition $def): string
    {
        if (is_countable($def->value) && count($def->value) !== 2) {
            throw new InvalidArgumentException('Expected: 2 values for BETWEEN condition. Got: ' . count($def->value) . '.', [
                'definition' => $def,
            ]);
        }

        return implode(' ', [
            $this->asColumn($def->column),
            $def->operator->value,
            $this->asPlaceholder($def->value[0]),
            Logic::And->value,
            $this->asPlaceholder($def->value[1]),
        ]);
    }

    /**
     * @param ComparingCondition $def
     * @return string
     */
    protected function formatConditionForRange(ComparingCondition $def): string
    {
        $column = $this->asColumn($def->column);
        $negated = $def->operator === Operator::NotInRange;
        $logic = $negated ? Logic::Or : Logic::And;
        $value = $def->value;
        if ($value instanceof Bounds) {
            return implode(' ', [
                $column,
                $value->getLowerOperator($negated),
                '?',
                $logic->value,
                $column,
                $value->getUpperOperator($negated),
                '?',
            ]);
        }

        $message = 'Value for WHERE with range. ';
        $message .= 'Expected: Bounds. Got: ' . get_debug_type($value) . '.';
        throw new NotSupportedException($message, [
            'definition' => $def,
        ]);
    }

    /**
     * @param ComparingCondition $def
     * @return string
     */
    protected function formatConditionForOperator(ComparingCondition $def): string
    {
        return implode(' ', [
            $this->asColumn($def->column),
            $def->operator->value,
            $this->asPlaceholder($def->value),
        ]);
    }

    /**
     * @param ComparingCondition $def
     * @return string
     */
    protected function formatConditionForNull(ComparingCondition $def): string
    {
        return $this->asColumn($def->column) . match ($def->operator) {
            Operator::Equals => ' IS NULL',
            Operator::NotEquals => ' IS NOT NULL',
            default => throw new LogicException("Invalid operator: {$def->operator->value} for NULL condition.", [
                'definition' => $def,
            ]),
        };
    }

    /**
     * @param QueryStatement $statement
     * @return string
     */
    protected function formatSubQuery(QueryStatement $statement): string
    {
        return '(' . $statement->generateTemplate($this) . ')';
    }

    /**
     * @param SelectStatement $statement
     * @return string
     */
    protected function formatGroupByPart(SelectStatement $statement): string
    {
        return $statement->groupBy !== null
            ? 'GROUP BY ' . $this->asCsv($this->asColumns($statement->groupBy))
            : '';
    }

    /**
     * @param SelectStatement $statement
     * @return string
     */
    protected function formatHavingPart(SelectStatement $statement): string
    {
        return $statement->having !== null
            ? 'HAVING ' . $this->formatCondition($statement->having)
            : '';
    }

    /**
     * @param array<string, Ordering>|null $orderBy
     * @return string
     */
    protected function formatOrderByPart(?array $orderBy): string
    {
        if ($orderBy === null) {
            return '';
        }
        $clauses = [];
        foreach ($orderBy as $column => $ordering) {
            $clauses[] = $this->concat([
                $this->asIdentifier($column),
                $this->formatSortOrderingPart($column, $ordering),
                $this->formatNullOrderingPart($column, $ordering),
            ]);
        }
        return 'ORDER BY ' . $this->asCsv($clauses);
    }

    /**
     * @param string $column
     * @param Ordering $ordering
     * @return string
     */
    protected function formatSortOrderingPart(string $column, Ordering $ordering): string
    {
        return $ordering->sort === SortOrder::Ascending
            ? ''
            : $ordering->sort->value;
    }

    /**
     * @param string $column
     * @param Ordering $ordering
     * @return string
     */
    abstract protected function formatNullOrderingPart(string $column, Ordering $ordering): string;

    /**
     * @param int|null $limit
     * @return string
     */
    protected function formatLimitPart(?int $limit): string
    {
        return $limit !== null ? "LIMIT {$limit}" : '';
    }

    /**
     * @param int|null $offset
     * @return string
     */
    protected function formatOffsetPart(?int $offset): string
    {
        return $offset !== null ? "OFFSET {$offset}" : '';
    }

    /**
     * @param list<string>|null $columns
     * @return string
     */
    protected function formatReturningPart(?array $columns): string
    {
        if ($columns === null) {
            return '';
        }

        if (count($columns) === 0) {
            $columns[] = '*';
        }

        return 'RETURNING ' . $this->asCsv($this->asColumns($columns));
    }

    /**
     * @param QueryFunction $func
     * @return string
     */
    public function formatFunction(QueryFunction $func): string
    {
        return $this->concat([
            $this->formatFunctionNamePart($func),
            $this->formatWindowFunctionPart($func->window?->definition),
            $func->as !== null ? 'AS ' . $this->asIdentifier($func->as) : null,
        ]);
    }

    /**
     * @param QueryFunction $func
     * @return string
     */
    protected function formatFunctionNamePart(QueryFunction $func): string
    {
        $column = $func->column !== null
            ? $this->asColumn($func->column)
            : '';
        return $func::$name . '(' . $column . ')';
    }

    /**
     * @param WindowDefinition|null $def
     * @return string
     */
    protected function formatWindowFunctionPart(?WindowDefinition $def): string
    {
        if ($def === null) {
            return '';
        }

        $parts = [];
        if ($def->partitionBy) {
            $parts[] = 'PARTITION BY ' . $this->asCsv($this->asIdentifiers($def->partitionBy));
        }
        if ($def->orderBy !== null) {
            $clauses = [];
            foreach ($def->orderBy as $column => $ordering) {
                $clauses[] = $this->concat([
                    $this->asIdentifier($column),
                    $this->formatSortOrderingPart($column, $ordering),
                    $this->formatNullOrderingPart($column, $ordering),
                ]);
            }
            $parts[] = 'ORDER BY ' . $this->asCsv($clauses);
        }
        return 'OVER (' . implode(' ', $parts) . ')';
    }

    /**
     * @param Tags|null $tags
     * @return string
     */
    public function formatTags(?Tags $tags): string
    {
        if ($tags === null || count($tags) === 0) {
            return '';
        }
        return match ($this->databaseConfig->tagsFormat) {
            TagsFormat::Log => $this->formatTagsForLogs($tags),
            TagsFormat::OpenTelemetry => $this->formatTagsForOpenTelemetry($tags),
        };
    }

    /**
     * @param Tags $tags
     * @return string
     */
    protected function formatTagsForLogs(Tags $tags): string
    {
        $fields = Arr::map($tags, static fn(mixed $v, string $k) => rawurlencode($k) . '=' . rawurlencode((string) $v));
        return $this->asBlockComment(implode(',', $fields));
    }

    /**
     * @param Tags $tags
     * @return string
     */
    protected function formatTagsForOpenTelemetry(Tags $tags): string
    {
        $fields = Arr::map($tags, static fn(mixed $v, string $k) => rawurlencode($k) . "='" . rawurlencode((string) $v) . "'");
        return $this->asBlockComment($this->asCsv($fields));
    }

    /**
     * @param string $name
     * @return string
     */
    public function asTable(string $name): string
    {
        $as = null;
        if (preg_match('/( AS | as )/', $name)) {
            $dlm = preg_quote($this->identifierDelimiter);
            $tablePatternPart = $dlm . '?(?<table>[^ ' . $dlm . ']+)' . $dlm . '?';
            $asPatternPart = '( (AS|as) ' . $dlm . '?(?<as>[^' . $dlm . ']+)' . $dlm . '?)?';
            $pattern = '/^' . $tablePatternPart . $asPatternPart . '$/';
            $match = null;
            if (preg_match($pattern, $name, $match)) {
                $name = (string) $match['table'];
                $as = $match['as'] ?? null;
            }
        }

        $name = str_contains($name, '.')
            ? implode('.', $this->asIdentifiers(explode('.', $name)))
            : $this->asIdentifier($name);

        if ($as !== null) {
            $name .= ' AS ' . $this->asIdentifier($as);
        }
        return $name;
    }

    /**
     * @param mixed $value
     * @return string
     */
    protected function asPlaceholder(mixed $value): string
    {
        return match (true) {
            is_iterable($value) => $this->asEnclosedCsv($this->asPlaceholders($value)),
            $value instanceof Expression => $value->toValue($this),
            default => '?',
        };
    }

    /**
     * @param iterable<int, mixed> $values
     * @return list<string>
     */
    protected function asPlaceholders(iterable $values): array
    {
        return array_map($this->asPlaceholder(...), Arr::values($values));
    }

    /**
     * @param list<mixed> $parameters
     * @param QueryStatement $statement
     * @return void
     */
    protected function addParametersForCte(array &$parameters, QueryStatement $statement): void
    {
        if ($statement->with !== null) {
            foreach ($statement->with as $with) {
                $as = $with->as;
                match (true) {
                    $as instanceof SelectStatement => $this->addParametersForSelect($parameters, $as),
                    $as instanceof RawStatement => $this->addParametersForRaw($parameters, $as),
                    default => throw new LogicException('Invalid CTE statement: ' . $as::class, [
                        'statement' => $as,
                    ]),
                };
            }
        }
    }

    /**
     * @param list<mixed> $parameters
     * @param SelectStatement $statement
     */
    protected function addParametersForJoins(array &$parameters, SelectStatement $statement): void
    {
        if ($statement->joins !== null) {
            $conditions = array_map(static fn(JoinDefinition $join) => $join->condition, $statement->joins);
            $conditions = array_filter($conditions, static fn($def) => $def !== null);
            foreach ($conditions as $condition) {
                $this->addParametersForCondition($parameters, $condition);
            }
        }
    }

    /**
     * @param list<mixed> $parameters
     * @param ConditionStatement $statement
     * @return void
     */
    protected function addParametersForWhere(array &$parameters, ConditionStatement $statement): void
    {
        if ($statement->where !== null) {
            $this->addParametersForCondition($parameters, $statement->where);
        }
    }

    /**
     * @param list<mixed> $parameters
     * @param SelectStatement $statement
     * @return void
     */
    protected function addParametersForHaving(array &$parameters, SelectStatement $statement): void
    {
        if ($statement->having !== null) {
            $this->addParametersForCondition($parameters, $statement->having);
        }
    }

    /**
     * @param list<mixed> $parameters
     * @param SelectStatement $statement
     * @return void
     */
    protected function addParametersForCompound(array &$parameters, SelectStatement $statement): void
    {
        if ($statement->compound !== null) {
            $this->addParametersForSelect($parameters, $statement->compound->query);
        }
    }

    /**
     * @param list<mixed> $parameters
     * @param Condition $def
     * @return void
     */
    protected function addParametersForCondition(array &$parameters, Condition $def): void
    {
        do {
            $value = $def->value;
            match (true) {
                is_iterable($value) => array_push($parameters, ...iterator_to_array($value)),
                $value instanceof Condition => $this->addParametersForCondition($parameters, $value),
                $value instanceof QueryStatement => array_push($parameters, ...$value->generateParameters($this)),
                $value instanceof Expression => null, // already converted to string in `self::asPlaceholder`.
                default => $parameters[] = $value,
            };
        } while ($def = $def->next);
    }

    /**
     * @param list<mixed> $parameters
     * @param Dataset $dataset
     * @return void
     */
    protected function addParametersForDataset(array &$parameters, Dataset $dataset): void
    {
        $columns = $dataset->getColumns();
        foreach ($dataset as $data) {
            foreach ($columns as $column) {
                if (array_key_exists($column, $data)) {
                    $value = $data[$column];
                    if (!$value instanceof Expression) {
                        $parameters[] = $value;
                    }
                    // expression is already evaluated to string
                }
            }
        }
    }

    /**
     * @param ListTablesStatement $statement
     * @return string
     */
    public function prepareTemplateForListTables(ListTablesStatement $statement): string
    {
        $database = $this->asLiteral($this->connectionConfig->getTableSchema());
        return "SELECT TABLE_NAME as `name` FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = {$database}";
    }

    /**
     * @param TableExistsStatement $statement
     * @return string
     */
    public function prepareTemplateForTableExists(TableExistsStatement $statement): string
    {
        $database = $this->asLiteral($this->connectionConfig->getTableSchema());
        $table = $this->asLiteral($statement->table);
        return implode(' ', [
            "SELECT 1 FROM INFORMATION_SCHEMA.TABLES",
            "WHERE TABLE_SCHEMA = {$database}",
            "AND TABLE_NAME = {$table}",
        ]);
    }

    /**
     * @param ListColumnsStatement $statement
     * @return string
     */
    abstract public function prepareTemplateForListColumns(ListColumnsStatement $statement): string;

    /**
     * @param stdClass $row
     * @return stdClass|null
     */
    abstract public function normalizeListTables(stdClass $row): ?stdClass;

    /**
     * @param stdClass $row
     * @return stdClass|null
     */
    abstract public function normalizeListColumns(stdClass $row): ?stdClass;

    /**
     * @param ListIndexesStatement $statement
     * @return string
     */
    abstract public function prepareTemplateForListIndexes(ListIndexesStatement $statement): string;

    /**
     * @param ListForeignKeysStatement $statement
     * @return string
     */
    abstract public function prepareTemplateForListForeignKeys(ListForeignKeysStatement $statement): string;
}
