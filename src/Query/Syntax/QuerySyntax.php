<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Syntax;

use Kirameki\Collections\Utils\Arr;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Core\Exceptions\NotSupportedException;
use Kirameki\Core\Exceptions\UnreachableException;
use Kirameki\Core\Value;
use Kirameki\Database\Exceptions\DropProtectionException;
use Kirameki\Database\Expression;
use Kirameki\Database\Info\Statements\ListColumnsStatement;
use Kirameki\Database\Info\Statements\ListForeignKeysStatement;
use Kirameki\Database\Info\Statements\ListIndexesStatement;
use Kirameki\Database\Info\Statements\ListTablesStatement;
use Kirameki\Database\Info\Statements\TableExistsStatement;
use Kirameki\Database\Query\Expressions\Aggregate;
use Kirameki\Database\Query\Statements\CompoundDefinition;
use Kirameki\Database\Query\Statements\ConditionDefinition;
use Kirameki\Database\Query\Statements\ConditionsStatement;
use Kirameki\Database\Query\Statements\DeleteStatement;
use Kirameki\Database\Query\Statements\InsertStatement;
use Kirameki\Database\Query\Statements\JoinDefinition;
use Kirameki\Database\Query\Statements\QueryStatement;
use Kirameki\Database\Query\Statements\SelectStatement;
use Kirameki\Database\Query\Statements\UpdateStatement;
use Kirameki\Database\Query\Statements\UpsertStatement;
use Kirameki\Database\Query\Statements\WithDefinition;
use Kirameki\Database\Query\Support\Bounds;
use Kirameki\Database\Query\Support\Dataset;
use Kirameki\Database\Query\Support\Lock;
use Kirameki\Database\Query\Support\LockOption;
use Kirameki\Database\Query\Support\LockType;
use Kirameki\Database\Query\Support\Operator;
use Kirameki\Database\Query\Support\Ordering;
use Kirameki\Database\Query\Support\Tags;
use Kirameki\Database\Query\Support\TagsFormat;
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
use function implode;
use function is_array;
use function is_bool;
use function is_iterable;
use function is_null;
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
     * @param Tags|null $tags
     * @return string
     */
    public function interpolate(string $template, array $parameters, ?Tags $tags = null): string
    {
        $parameters = $this->stringifyParameters($parameters);
        $parameters = Arr::flatten($parameters);
        $remains = count($parameters);

        $interpolated = (string) preg_replace_callback('/\?\??/', function($matches) use ($template, &$parameters, &$remains) {
            if ($matches[0] === '??') {
                return '??';
            }
            if ($remains > 0) {
                $value = current($parameters);
                next($parameters);
                $remains--;
                return match (true) {
                    is_null($value) => 'NULL',
                    is_bool($value) => $value ? 'TRUE' : 'FALSE',
                    is_string($value) => $this->asLiteral($value),
                    default => (string) $value,
                };
            }
            throw new UnreachableException('No more parameters to interpolate', [
                'template' => $template,
                'parameters' => $parameters,
            ]);
        }, $template);

        return $interpolated . $this->formatTags($tags);
    }

    /**
     * @param SelectStatement $statement
     * @return string
     */
    public function prepareTemplateForSelect(SelectStatement $statement): string
    {
        $query = $this->concat([
            $this->formatWithPart($statement),
            $this->formatSelectPart($statement),
            $this->formatFromPart($statement),
            $this->formatJoinPart($statement),
            $this->formatWherePart($statement),
            $this->formatGroupByPart($statement),
            $this->formatHavingPart($statement),
            $this->formatOrderByPart($statement->orderBy),
            $this->formatLimitPart($statement->limit),
            $this->formatOffsetPart($statement->offset),
        ]);

        return $this->formatCompoundPart($query, $statement->compound);
    }

    /**
     * @param SelectStatement $statement
     * @return list<mixed>
     */
    public function prepareParametersForSelect(SelectStatement $statement): array
    {
        $parameters = [];
        $this->addParametersForJoins($parameters, $statement);
        $this->addParametersForWhere($parameters, $statement);
        $this->addParametersForHaving($parameters, $statement);
        return $this->stringifyParameters($parameters);
    }

    /**
     * @param InsertStatement $statement
     * @param list<string> $columns
     * @return string
     */
    public function prepareTemplateForInsert(InsertStatement $statement, array $columns): string
    {
        if ($columns === []) {
            return "INSERT INTO {$this->asIdentifier($statement->table)} DEFAULT VALUES";
        }

        return 'INSERT INTO ' . $this->concat([
            $this->asIdentifier($statement->table),
            $this->formatDatasetColumnsPart($columns),
            'VALUES',
            $this->formatInsertDatasetValuesPart($statement->dataset, $columns),
            $this->formatReturningPart($statement->returning),
        ]);
    }

    /**
     * @param InsertStatement $statement
     * @param list<string> $columns
     * @return list<mixed>
     */
    public function prepareParametersForInsert(InsertStatement $statement, array $columns): array
    {
        return $this->formatDatasetParameters($statement, $statement->dataset, $columns);
    }

    /**
     * @param UpsertStatement $statement
     * @param list<string> $columns
     * @return string
     */
    public function prepareTemplateForUpsert(UpsertStatement $statement, array $columns): string
    {
        return 'INSERT INTO ' . $this->concat([
            $this->asIdentifier($statement->table),
            $this->formatDatasetColumnsPart($columns),
            'VALUES',
            $this->formatUpsertDatasetValuesPart($statement->dataset, $columns),
            $this->formatUpsertOnConflictPart($statement->onConflict),
            $this->formatUpsertUpdateSet($columns),
            $this->formatReturningPart($statement->returning),
        ]);
    }

    /**
     * @param UpsertStatement $statement
     * @param list<string> $columns
     * @return list<mixed>
     */
    public function prepareParametersForUpsert(UpsertStatement $statement, array $columns): array
    {
        return $this->formatDatasetParameters($statement, $statement->dataset, $columns);
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
            $this->formatConditionsPart($statement),
            $this->formatReturningPart($statement->returning),
        ]);
    }

    /**
     * @param UpdateStatement $statement
     * @return list<mixed>
     */
    public function prepareParametersForUpdate(UpdateStatement $statement): array
    {
        $parameters = $statement->set ?? throw new LogicException('No values to update', ['statement' => $statement]);
        $parameters = array_values($parameters);
        $this->addParametersForWhere($parameters, $statement);
        return $this->stringifyParameters($parameters);
    }

    /**
     * @param DeleteStatement $statement
     * @return string
     */
    public function prepareTemplateForDelete(DeleteStatement $statement): string
    {
        if ($this->databaseConfig->dropProtection && count($statement->where ?? []) === 0) {
            throw new DropProtectionException('DELETE without a WHERE clause is prohibited by configuration.', [
                'statement' => $statement,
            ]);
        }

        return $this->concat([
            $this->formatWithPart($statement),
            'DELETE FROM',
            $this->asIdentifier($statement->table),
            $this->formatConditionsPart($statement),
            $this->formatReturningPart($statement->returning),
        ]);
    }

    /**
     * @param DeleteStatement $statement
     * @return list<mixed>
     */
    public function prepareParametersForDelete(DeleteStatement $statement): array
    {
        $parameters = [];
        $this->addParametersForWhere($parameters, $statement);
        return $this->stringifyParameters($parameters);
    }

    /**
     * @param ConditionsStatement $statement
     * @return string
     */
    protected function formatWithPart(ConditionsStatement $statement): string
    {
        return $statement->with !== null
            ? 'WITH ' . implode(', ', array_map($this->formatWithDefinition(...), $statement->with))
            : '';
    }

    /**
     * @param WithDefinition $with
     * @return string
     */
    protected function formatWithDefinition(WithDefinition $with): string
    {
        return $this->concat([
            $this->asIdentifier($with->name),
            $with->recursive ? 'RECURSIVE' : null,
            'AS',
            $this->formatSubQuery($with->statement),
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
            $this->formatSelectLockPart($statement->lock),
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

        return $this->asCsv($this->asColumns($columns));
    }

    /**
     * @param Lock|null $lock
     * @return string
     */
    protected function formatSelectLockPart(?Lock $lock): string
    {
        $type = $lock?->type;

        if ($type === null) {
            return '';
        }

        return match($type) {
            LockType::Exclusive => $type->value . $this->formatSelectLockOptionPart($lock->option),
            LockType::Shared => $type->value,
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
                $expr .= ' ON ' . $this->formatConditionDefinition($def->condition);
            }
            return $expr;
        }, $joins));
    }

    /**
     * @param string $query
     * @param CompoundDefinition|null $compound
     * @return string
     */
    protected function formatCompoundPart(string $query, ?CompoundDefinition $compound): string
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
     * @param QueryStatement $statement
     * @param Dataset $dataset
     * @param list<string> $columns
     * @return list<mixed>
     */
    protected function formatDatasetParameters(QueryStatement $statement, Dataset $dataset, array $columns): array
    {
        $parameters = [];
        foreach ($dataset as $data) {
            foreach ($columns as $column) {
                if (array_key_exists($column, $data)) {
                    $parameters[] = $data[$column];
                }
            }
        }
        return $this->stringifyParameters($parameters);
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
                $binders[] = array_key_exists($column, $data) ? '?' : 'DEFAULT';
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
        $clause = 'ON CONFLICT ';
        if (count($onConflict) === 0) {
            return $clause;
        }
        return $clause . $this->asEnclosedCsv($this->asIdentifiers($onConflict));
    }

    /**
     * @param list<string> $columns
     * @return string
     */
    protected function formatUpsertUpdateSet(array $columns): string
    {
        $columns = $this->asIdentifiers($columns);
        $columns = array_map(static fn(string $column): string => "{$column} = EXCLUDED.{$column}", $columns);
        return 'DO UPDATE SET ' . implode(', ', $columns);
    }

    /**
     * @param ConditionsStatement $statement
     * @return string
     */
    protected function formatConditionsPart(ConditionsStatement $statement): string
    {
        return $this->concat([
            $this->formatWherePart($statement),
            $this->formatOrderByPart($statement->orderBy),
            $this->formatLimitPart($statement->limit),
        ]);
    }

    /**
     * @param ConditionsStatement $statement
     * @return string
     */
    protected function formatWherePart(ConditionsStatement $statement): string
    {
        return $statement->where !== null
            ? 'WHERE ' . implode(' AND ', array_map($this->formatConditionDefinition(...), $statement->where))
            : '';
    }

    /**
     * @param ConditionDefinition $def
     * @return string
     */
    protected function formatConditionDefinition(ConditionDefinition $def): string
    {
        $parts = [];
        $parts[] = $this->formatConditionSegment($def);

        // Dig through all chained clauses if exists
        while (($logic = $def->nextLogic) && ($def = $def->next)) {
            $parts[] = $logic . ' ' . $this->formatConditionSegment($def);
        }

        $merged = implode(' ', $parts);
        return (count($parts) > 1)
            ? '(' . $merged . ')'
            : $merged;
    }

    /**
     * @param ConditionDefinition $def
     * @return string
     */
    protected function formatConditionSegment(ConditionDefinition $def): string
    {
        return match ($def->operator) {
            Operator::Raw => $this->formatConditionForRaw($def),
            Operator::Equals => $this->formatConditionForEqual($def),
            Operator::LessThanOrEqualTo => $this->formatConditionForLessThanOrEqualTo($def),
            Operator::LessThan => $this->formatConditionForLessThan($def),
            Operator::GreaterThanOrEqualTo => $this->formatConditionForGreaterThanOrEqualTo($def),
            Operator::GreaterThan => $this->formatConditionForGreaterThan($def),
            Operator::In => $this->formatConditionForIn($def),
            Operator::Between => $this->formatConditionForBetween($def),
            Operator::Exists => $this->formatConditionForExists($def),
            Operator::Like => $this->formatConditionForLike($def),
            Operator::Range => $this->formatConditionForRange($def),
            default => throw new NotSupportedException('Operator: ' . Value::getType($def->operator?->value)),
        };
    }

    /**
     * @param ConditionDefinition $def
     * @return string
     */
    protected function formatConditionForRaw(ConditionDefinition $def): string
    {
        if ($def->value instanceof Expression) {
            return $def->value->toValue($this);
        }

        throw new NotSupportedException('Condition: ' . Value::getType($def->value));
    }

    /**
     * @param ConditionDefinition $def
     * @return string
     */
    protected function formatConditionForEqual(ConditionDefinition $def): string
    {
        $column = $this->asColumn($def->column, true);
        $operator = $def->negated ? '!=' : '=';
        $value = $def->value;

        if ($value === null) {
            return $column . ' ' . ($def->negated ? 'IS NOT NULL' : 'IS NULL');
        }

        return $this->formatConditionForOperator($column, $operator, $value);
    }

    /**
     * @param ConditionDefinition $def
     * @return string
     */
    protected function formatConditionForLessThanOrEqualTo(ConditionDefinition $def): string
    {
        $column = $this->asColumn($def->column, true);
        $operator = $def->negated ? '>' : '<=';
        $value = $def->value;
        return $this->formatConditionForOperator($column, $operator, $value);
    }

    /**
     * @param ConditionDefinition $def
     * @return string
     */
    protected function formatConditionForLessThan(ConditionDefinition $def): string
    {
        $column = $this->asColumn($def->column, true);
        $operator = $def->negated ? '>=' : '<';
        $value = $def->value;
        return $this->formatConditionForOperator($column, $operator, $value);
    }

    /**
     * @param ConditionDefinition $def
     * @return string
     */
    protected function formatConditionForGreaterThanOrEqualTo(ConditionDefinition $def): string
    {
        $column = $this->asColumn($def->column, true);
        $operator = $def->negated ? '<' : '>=';
        $value = $def->value;
        return $this->formatConditionForOperator($column, $operator, $value);
    }

    /**
     * @param ConditionDefinition $def
     * @return string
     */
    protected function formatConditionForGreaterThan(ConditionDefinition $def): string
    {
        $column = $this->asColumn($def->column, true);
        $operator = $def->negated ? '<=' : '>';
        $value = $def->value;
        return $this->formatConditionForOperator($column, $operator, $value);
    }

    /**
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @return string
     */
    protected function formatConditionForOperator(string $column, string $operator, mixed $value): string
    {
        return $column . ' ' . $operator . ' ' . $this->asPlaceholder($value);
    }

    /**
     * @param ConditionDefinition $def
     * @return string
     */
    protected function formatConditionForIn(ConditionDefinition $def): string
    {
        $column = $this->asColumn($def->column, true);
        $operator = $def->negated ? 'NOT IN' : 'IN';
        $value = $def->value;

        if (is_iterable($value)) {
            $value = iterator_to_array($value);
        }

        if (is_array($value)) {
            return count($value) > 0
                ? $this->formatConditionForOperator($column, $operator, $value)
                : '1 = 0';
        }

        if ($value instanceof QueryStatement) {
            return "{$column} {$operator} {$this->formatSubQuery($value)}";
        }

        throw new NotSupportedException('WHERE ' . $operator . ' value: ' . Value::getType($value), [
            'definition' => $def,
        ]);
    }

    /**
     * @param ConditionDefinition $def
     * @return string
     */
    protected function formatConditionForBetween(ConditionDefinition $def): string
    {
        $column = $this->asColumn($def->column, true);
        $operator = $def->negated ? 'NOT BETWEEN' : 'BETWEEN';
        $min = $this->asPlaceholder($def->value[0]);
        $max = $this->asPlaceholder($def->value[1]);
        return "{$column} {$operator} {$min} AND {$max}";
    }

    /**
     * @param ConditionDefinition $def
     * @return string
     */
    protected function formatConditionForExists(ConditionDefinition $def): string
    {
        $column = $this->asColumn($def->column, true);
        $operator = $def->negated ? 'NOT EXISTS' : 'EXISTS';
        $value = $def->value;

        if ($value instanceof QueryStatement) {
            return "{$column} {$operator} {$this->formatSubQuery($value)}";
        }

        throw new NotSupportedException('WHERE ' . $operator . ' value: ' . Value::getType($value), [
            'definition' => $def,
        ]);
    }

    /**
     * @param ConditionDefinition $def
     * @return string
     */
    protected function formatConditionForLike(ConditionDefinition $def): string
    {
        $column = $this->asColumn($def->column, true);
        $operator = $def->negated ? 'NOT LIKE' : 'LIKE';
        $value = $def->value;
        return $this->formatConditionForOperator($column, $operator, $value);
    }

    /**
     * @param ConditionDefinition $def
     * @return string
     */
    protected function formatConditionForRange(ConditionDefinition $def): string
    {
        $column = $this->asColumn($def->column, true);
        $negated = $def->negated;
        $value = $def->value;
        if ($value instanceof Bounds) {
            $lowerOperator = $value->getLowerOperator($negated);
            $upperOperator = $value->getUpperOperator($negated);
            return $negated
                ? "{$column} {$lowerOperator} ? OR {$column} {$upperOperator} ?"
                : "{$column} {$lowerOperator} ? AND {$column} {$upperOperator} ?";
        }

        throw new NotSupportedException('WHERE ranged value: ' . Value::getType($value), [
            'definition' => $def,
        ]);
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
            ? 'HAVING ' . implode(' AND ', array_map($this->formatConditionDefinition(...), $statement->having))
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
        return $ordering->sort->value;
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
     * @param list<string>|null $returning
     * @return string
     */
    protected function formatReturningPart(?array $returning): string
    {
        if ($returning === null) {
            return '';
        }

        return "RETURNING {$this->asCsv($this->asIdentifiers($returning))}";
    }

    /**
     * @param Aggregate $aggregate
     * @return string
     */
    public function formatAggregate(Aggregate $aggregate): string
    {
        $function = $aggregate->function;
        if ($aggregate->column !== null) {
            $function .= '(' . $this->asColumn($aggregate->column) . ')';
        }

        return $this->concat([
            $function,
            $this->formatWindowFunction($aggregate),
            $aggregate->as !== null ? 'AS ' . $this->asIdentifier($aggregate->as) : null,
        ]);
    }

    /**
     * @param Aggregate $aggregate
     * @return string
     */
    protected function formatWindowFunction(Aggregate $aggregate): string
    {
        if (!$aggregate->isWindowFunction) {
            return '';
        }

        $parts = [];
        if ($aggregate->partitionBy) {
            $parts[] = 'PARTITION BY ' . $this->asCsv($this->asIdentifiers($aggregate->partitionBy));
        }
        if ($aggregate->orderBy !== null) {
            $clauses = [];
            foreach ($aggregate->orderBy as $column => $ordering) {
                $clauses[] = $this->concat([
                    $this->asIdentifier($column),
                    $this->formatSortOrderingPart($column, $ordering),
                    $this->formatNullOrderingPart($column, $ordering),
                ]);
            }
            $parts[] = 'ORDER BY ' . $this->asCsv($clauses);
        }
        return 'OVER(' . implode(' ', $parts) . ')';
    }

    /**
     * @param Tags|null $tags
     * @return string
     */
    public function formatTags(?Tags $tags): string
    {
        if ($tags === null) {
            return '';
        }
        return match($this->databaseConfig->tagsFormat) {
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
        return Arr::join($fields, ',', ' /* ', ' */');
    }

    /**
     * @param Tags $tags
     * @return string
     */
    protected function formatTagsForOpenTelemetry(Tags $tags): string
    {
        $fields = Arr::map($tags, static fn(mixed $v, string $k) => rawurlencode($k) . "='" . rawurlencode((string) $v) . "'");
        return Arr::join($fields, ',', ' /*', '*/');
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
     * @param mixed $name
     * @param bool $withAlias
     * @return string
     */
    public function asColumn(mixed $name, bool $withAlias = false): string
    {
        if ($name instanceof Expression) {
            return $name->toValue($this);
        }

        if (is_iterable($name)) {
            return $this->asEnclosedCsv($this->asColumns($name));
        }

        if (is_string($name)) {
            $table = null;
            $as = null;
            if (preg_match('/(\.| as | AS )/', $name)) {
                $dlm = preg_quote($this->identifierDelimiter);
                $patterns = [];
                $patterns[] = '(' . $dlm . '?(?<table>[^\.' . $dlm . ']+)' . $dlm . '?\.)?';
                $patterns[] = $dlm . '?(?<column>[^ ' . $dlm . ']+)' . $dlm . '?';
                if ($withAlias) {
                    $patterns[] = '( (AS|as) ' . $dlm . '?(?<as>[^' . $dlm . ']+)' . $dlm . '?)?';
                }
                $pattern = '/^' . implode('', $patterns) . '$/';
                $match = null;
                if (preg_match($pattern, $name, $match)) {
                    $table = $match['table'] !== '' ? $match['table'] : null;
                    $name = $match['column'];
                    $as = $match['as'] ?? null;
                }
            }
            if ($name !== '*') {
                $name = $this->asIdentifier($name);
            }
            if ($table !== null) {
                $name = $this->asIdentifier($table) . '.' . $name;
            }
            if ($as !== null) {
                $name .= ' AS ' . $this->asIdentifier($as);
            }
            return $name;
        }

        throw new NotSupportedException('Unknown column type: ' . Value::getType($name));
    }

    /**
     * @param iterable<int, mixed> $columns
     * @return list<string>
     */
    protected function asColumns(iterable $columns): array
    {
        return array_map($this->asColumn(...), Arr::values($columns));
    }

    /**
     * @param mixed $value
     * @return string
     */
    protected function asPlaceholder(mixed $value): string
    {
        return match (true) {
            $value instanceof Expression => $value->toValue($this),
            $value instanceof QueryStatement => $this->formatSubQuery($value),
            is_iterable($value) => $this->asEnclosedCsv($this->asParameterPlaceholders($value)),
            default => '?',
        };
    }

    /**
     * @param iterable<int, mixed> $values
     * @return list<string>
     */
    protected function asParameterPlaceholders(iterable $values): array
    {
        return array_map($this->asPlaceholder(...), Arr::values($values));
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
            $this->addParametersForConditions($parameters, $conditions);
        }
    }

    /**
     * @param list<mixed> $parameters
     * @param ConditionsStatement $statement
     * @return void
     */
    protected function addParametersForWhere(array &$parameters, ConditionsStatement $statement): void
    {
        if ($statement->where !== null) {
            $this->addParametersForConditions($parameters, $statement->where);
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
            $this->addParametersForConditions($parameters, $statement->having);
        }
    }

    /**
     * @param list<mixed> $parameters
     * @param iterable<int, ConditionDefinition> $conditions
     * @return void
     */
    protected function addParametersForConditions(array &$parameters, iterable $conditions): void
    {
        foreach ($conditions as $condition) {
            $this->addParametersForCondition($parameters, $condition);
        }
    }

    /**
     * @param list<mixed> $parameters
     * @param ConditionDefinition $def
     * @return void
     */
    protected function addParametersForCondition(array &$parameters, ConditionDefinition $def): void
    {
        while ($def !== null) {
            $value = $def->value;
            match (true) {
                is_iterable($value) => array_push($parameters, ...iterator_to_array($value)),
                $value instanceof QueryStatement => array_push($parameters, ...$value->generateParameters($this)),
                $value instanceof Expression => null, // already converted to string in `self::asPlaceholder`.
                default => $parameters[] = $value,
            };
            $def = $def->next;
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
    public function prepareTemplateForListColumns(ListColumnsStatement $statement): string
    {
        $database = $this->asLiteral($this->connectionConfig->getTableSchema());
        $table = $this->asLiteral($statement->table);
        $columns = implode(', ', [
            "COLUMN_NAME AS `name`",
            "DATA_TYPE AS `type`",
            "IS_NULLABLE AS `nullable`",
            "ORDINAL_POSITION AS `position`",
        ]);
        return implode(' ', [
            "SELECT {$columns} FROM INFORMATION_SCHEMA.COLUMNS",
            "WHERE TABLE_SCHEMA = {$database}",
            "AND TABLE_NAME = {$table}",
            "ORDER BY ORDINAL_POSITION ASC",
        ]);
    }

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
