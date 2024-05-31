<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Syntax;

use BackedEnum;
use DateTimeInterface;
use Iterator;
use Kirameki\Collections\Utils\Arr;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Core\Exceptions\NotSupportedException;
use Kirameki\Core\Exceptions\UnreachableException;
use Kirameki\Core\Json;
use Kirameki\Core\Value;
use Kirameki\Database\Config\ConnectionConfig;
use Kirameki\Database\Exceptions\DropProtectionException;
use Kirameki\Database\Info\Statements\ListColumnsStatement;
use Kirameki\Database\Info\Statements\ListForeignKeysStatement;
use Kirameki\Database\Info\Statements\ListIndexesStatement;
use Kirameki\Database\Info\Statements\ListTablesStatement;
use Kirameki\Database\Info\Statements\TableExistsStatement;
use Kirameki\Database\Query\Expressions\Aggregate;
use Kirameki\Database\Query\Expressions\Column;
use Kirameki\Database\Query\Expressions\Expression;
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
use Kirameki\Database\Query\Support\Dataset;
use Kirameki\Database\Query\Support\Lock;
use Kirameki\Database\Query\Support\LockOption;
use Kirameki\Database\Query\Support\LockType;
use Kirameki\Database\Query\Support\Operator;
use Kirameki\Database\Query\Support\Ordering;
use Kirameki\Database\Query\Support\Range;
use Kirameki\Database\Query\Support\Tags;
use Kirameki\Database\Query\Support\TagsFormat;
use Kirameki\Database\Syntax;
use stdClass;
use function array_fill;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_push;
use function count;
use function current;
use function explode;
use function implode;
use function is_array;
use function is_bool;
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
     * @param Tags|null $tags
     * @return string
     */
    public function interpolate(string $template, array $parameters, ?Tags $tags = null): string
    {
        $parameters = $this->stringifyValues($parameters);
        $remains = count($parameters);

        $interpolated = (string) preg_replace_callback('/\?\??/', function($matches) use (&$parameters, &$remains) {
            if ($matches[0] === '??') {
                return '??';
            }
            if ($remains > 0) {
                $current = current($parameters);
                next($parameters);
                $remains--;
                return match (true) {
                    is_bool($current) => $current ? 'TRUE' : 'FALSE',
                    is_string($current) => $this->asLiteral($current),
                    default => (string) $current,
                };
            }
            throw new UnreachableException('No more parameters to interpolate');
        }, $template);

        return $interpolated . $this->formatTags($tags);
    }

    /**
     * @param SelectStatement $statement
     * @return string
     */
    public function prepareTemplateForSelect(SelectStatement $statement): string
    {
        $query = implode(' ', array_filter([
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
        ]));

        return $this->formatCompoundPart($query, $statement->compound);
    }

    /**
     * @param SelectStatement $statement
     * @return array<mixed>
     */
    public function prepareParametersForSelect(SelectStatement $statement): array
    {
        return $this->stringifyValues($this->getParametersForConditions($statement));
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

        return 'INSERT INTO ' . implode(' ', array_filter([
            $this->asIdentifier($statement->table),
            $this->formatDatasetColumnsPart($columns),
            'VALUES',
            $this->formatDatasetValuesPart($statement->dataset, $columns),
            $this->formatReturningPart($statement->returning),
        ]));
    }

    /**
     * @param InsertStatement $statement
     * @param list<string> $columns
     * @return array<mixed>
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
        return 'INSERT INTO ' . implode(' ', array_filter([
            $this->asIdentifier($statement->table),
            $this->formatDatasetColumnsPart($columns),
            'VALUES',
            $this->formatDatasetValuesPart($statement->dataset, $columns),
            $this->formatUpsertOnConflictPart($statement->onConflict),
            $this->formatUpsertUpdateSet($columns),
            $this->formatReturningPart($statement->returning),
        ]));
    }

    /**
     * @param UpsertStatement $statement
     * @param list<string> $columns
     * @return array<mixed>
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
        return implode(' ', array_filter([
            $this->formatWithPart($statement),
            'UPDATE',
            $this->asIdentifier($statement->table),
            'SET',
            $this->formatUpdateAssignmentsPart($statement),
            $this->formatConditionsPart($statement),
            $this->formatReturningPart($statement->returning),
        ]));
    }

    /**
     * @param UpdateStatement $statement
     * @return array<mixed>
     */
    public function prepareParametersForUpdate(UpdateStatement $statement): array
    {
        $set = $statement->set ?? throw new LogicException('No values to update', ['statement' => $statement]);
        $parameters = array_merge($set, $this->getParametersForConditions($statement));
        return $this->stringifyValues($parameters);
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

        return implode(' ', array_filter([
            $this->formatWithPart($statement),
            'DELETE FROM',
            $this->asIdentifier($statement->table),
            $this->formatConditionsPart($statement),
            $this->formatReturningPart($statement->returning),
        ]));
    }

    /**
     * @param DeleteStatement $statement
     * @return array<mixed>
     */
    public function prepareParametersForDelete(DeleteStatement $statement): array
    {
        return $this->stringifyValues($this->getParametersForConditions($statement));
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
        return implode(' ', array_filter([
            $this->asIdentifier($with->name),
            $with->recursive ? 'RECURSIVE' : null,
            'AS',
            $this->formatSubQuery($with->statement),
        ]));
    }

    /**
     * @param SelectStatement $statement
     * @return string
     */
    protected function formatSelectPart(SelectStatement $statement): string
    {
        return 'SELECT ' . implode(' ', array_filter([
            $statement->distinct ? 'DISTINCT' : null,
            $this->formatSelectColumnsPart($statement),
            $this->formatSelectLockPart($statement->lock),
        ]));
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

        return $this->asCsv(array_map(function(string|Expression $column): string {
            return ($column instanceof Expression)
                ? $column->generateTemplate($this)
                : $this->asColumn($column);
        }, $columns));
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

        return ($type === LockType::Exclusive)
            ? $type->value . $this->formatSelectLockOptionPart($lock->option)
            : $type->value;
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
                ? $table->generateTemplate($this)
                : $this->asTable($table);
        }
        if (count($expressions) === 0) {
            return '';
        }
        return 'FROM ' . implode(' ', array_filter([
            $this->asCsv($expressions),
            $this->formatFromUseIndexPart($statement),
        ]));
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
            $expr .= $this->asTable($def->table) . ' ';
            $expr .= 'ON ' . $this->formatConditionDefinition($def->condition);
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

        return implode(' ', array_filter([
            $this->formatCompoundTemplate($query),
            $compound->operator->value,
            $this->formatCompoundTemplate($this->prepareTemplateForSelect($compound->query)),
            $this->formatOrderByPart($compound->orderBy),
            $this->formatLimitPart($compound->limit),
        ]));
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
        return $this->asEnclosedCsv(array_map($this->asColumn(...), $columns));
    }

    /**
     * @param Dataset $dataset
     * @param list<string> $columns
     * @return string
     */
    protected function formatDatasetValuesPart(Dataset $dataset, array $columns): string
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
     * @param QueryStatement $statement
     * @param Dataset $dataset
     * @param list<string> $columns
     * @return array<mixed>
     */
    protected function formatDatasetParameters(QueryStatement $statement, Dataset $dataset, array $columns): array
    {
        $parameters = [];
        foreach ($dataset as $index => $data) {
            if (!is_array($data)) {
                throw new LogicException('Data should be an array but ' . Value::getType($data) . ' given.', [
                    'statement' => $statement,
                    'dataset' => $dataset,
                    'index' => $index,
                ]);
            }
            foreach ($columns as $column) {
                if (array_key_exists($column, $data)) {
                    $parameters[] = $data[$column];
                }
            }
        }
        return $this->stringifyValues($parameters);
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
     * @param list<string> $onConflict
     * @return string
     */
    protected function formatUpsertOnConflictPart(array $onConflict): string
    {
        $clause = 'ON CONFLICT ';
        if (count($onConflict) === 0) {
            return $clause;
        }
        return $clause . $this->asEnclosedCsv(array_map($this->asIdentifier(...), $onConflict));
    }

    /**
     * @param list<string> $columns
     * @return string
     */
    protected function formatUpsertUpdateSet(array $columns): string
    {
        $columns = array_map($this->asIdentifier(...), $columns);
        $columns = array_map(static fn(string $column): string => "{$column} = EXCLUDED.{$column}", $columns);
        return 'DO UPDATE SET ' . implode(', ', $columns);
    }

    /**
     * @param ConditionsStatement $statement
     * @return string
     */
    protected function formatConditionsPart(ConditionsStatement $statement): string
    {
        return implode(' ', array_filter([
            $this->formatWherePart($statement),
            $this->formatOrderByPart($statement->orderBy),
            $this->formatLimitPart($statement->limit),
        ]));
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
            return $def->value->generateTemplate($this);
        }

        throw new NotSupportedException('Condition: ' . Value::getType($def->value));
    }

    /**
     * @param ConditionDefinition $def
     * @return string
     */
    protected function formatConditionForEqual(ConditionDefinition $def): string
    {
        $column = $this->getDefinedColumn($def);
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
        $column = $this->getDefinedColumn($def);
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
        $column = $this->getDefinedColumn($def);
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
        $column = $this->getDefinedColumn($def);
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
        $column = $this->getDefinedColumn($def);
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
        return $column . ' ' . $operator . ' ' . match (true) {
                $value instanceof QueryStatement => $this->formatSubQuery($value),
                $value instanceof Expression => $value->generateTemplate($this),
                default => '?',
            };
    }

    /**
     * @param ConditionDefinition $def
     * @return string
     */
    protected function formatConditionForIn(ConditionDefinition $def): string
    {
        $column = $this->getDefinedColumn($def);
        $operator = $def->negated ? 'NOT IN' : 'IN';
        $value = $def->value;

        if (is_array($value)) {
            $size = count($value);
            if ($size > 0) {
                $enclosedCsv = $this->asEnclosedCsv(array_fill(0, $size, '?'));
                return "{$column} {$operator} {$enclosedCsv}";
            }
            return '1 = 0';
        }

        if ($value instanceof QueryStatement) {
            $subQuery = $this->formatSubQuery($value);
            return "{$column} {$operator} {$subQuery}";
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
        $column = $this->getDefinedColumn($def);
        $operator = $def->negated ? 'NOT BETWEEN' : 'BETWEEN';
        return "{$column} {$operator} ? AND ?";
    }

    /**
     * @param ConditionDefinition $def
     * @return string
     */
    protected function formatConditionForExists(ConditionDefinition $def): string
    {
        $column = $this->getDefinedColumn($def);
        $operator = $def->negated ? 'NOT EXISTS' : 'EXISTS';
        $value = $def->value;

        if ($value instanceof QueryStatement) {
            $subQuery = $this->formatSubQuery($value);
            return "{$column} {$operator} {$subQuery}";
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
        $column = $this->getDefinedColumn($def);
        $operator = $def->negated ? 'NOT LIKE' : 'LIKE';
        return "{$column} {$operator} ?";
    }

    /**
     * @param ConditionDefinition $def
     * @return string
     */
    protected function formatConditionForRange(ConditionDefinition $def): string
    {
        $column = $this->getDefinedColumn($def);
        $negated = $def->negated;
        $value = $def->value;

        if ($value instanceof Range) {
            $lowerOperator = $negated
                ? ($value->lowerClosed ? '<' : '<=')
                : ($value->lowerClosed ? '>=' : '>');
            $upperOperator = $negated
                ? ($value->upperClosed ? '>' : '>=')
                : ($value->upperClosed ? '<=' : '<');
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
        if ($statement->groupBy === null) {
            return '';
        }
        return 'GROUP BY ' . $this->asCsv(array_map($this->asColumn(...), $statement->groupBy));
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
            $clauses[] = implode(' ', array_filter([
                $this->asIdentifier($column),
                $this->formatSortOrderingPart($column, $ordering),
                $this->formatNullOrderingPart($column, $ordering),
            ]));
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

        $columns = array_map($this->asIdentifier(...), $returning);

        return "RETURNING {$this->asCsv($columns)}";
    }

    /**
     * @param Aggregate $aggregate
     * @return string
     */
    public function formatAggregate(Aggregate $aggregate): string
    {
        return implode(' ', array_filter([
            $aggregate->function,
            $aggregate->column !== null ? $this->asColumn($aggregate->column) : null,
            $this->formatWindowFunction($aggregate),
            $aggregate->as !== null ? 'AS ' . $this->asIdentifier($aggregate->as) : null,
        ]));
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
            $parts[] = 'PARTITION BY ' . $this->asCsv(array_map($this->asIdentifier(...), $aggregate->partitionBy));
        }
        if ($aggregate->orderBy !== null) {
            $clauses = [];
            foreach ($aggregate->orderBy as $column => $ordering) {
                $clauses[] = implode(' ', array_filter([
                    $this->asIdentifier($column),
                    $this->formatSortOrderingPart($column, $ordering),
                    $this->formatNullOrderingPart($column, $ordering),
                ]));
            }
            $parts[] = 'ORDER BY ' . $this->asCsv($clauses);
        }
        return 'OVER(' . implode(' ', $parts) . ')';
    }

    /**
     * @param string $column
     * @param string $path
     * @return string
     */
    public function formatJsonExtract(string $column, string $path): string
    {
        return "{$this->asColumn($column)} -> \"$path\"";
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
            ? implode('.', array_map($this->asIdentifier(...), explode('.', $name)))
            : $this->asIdentifier($name);

        if ($as !== null) {
            $name .= ' AS ' . $this->asIdentifier($as);
        }
        return $name;
    }

    /**
     * @param string $name
     * @param bool $withAlias
     * @return string
     */
    public function asColumn(string $name, bool $withAlias = false): string
    {
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

    /**
     * @param ConditionDefinition $def
     * @return string
     */
    protected function getDefinedColumn(ConditionDefinition $def): string
    {
        $column = $def->column;

        if (is_string($column)) {
            return $this->asColumn($column);
        }

        if ($column instanceof Column) {
            return $column->generateTemplate($this);
        }

        throw new NotSupportedException('Unknown column type: ' . Value::getType($column), [
            'definition' => $def,
        ]);
    }

    /**
     * @param ConditionsStatement $statement
     * @return array<mixed>
     */
    protected function getParametersForConditions(ConditionsStatement $statement): array
    {
        $parameters = [];
        if ($statement->where !== null) {
            foreach ($statement->where as $cond) {
                $this->addParametersForCondition($parameters, $cond);
            }
        }
        return $parameters;
    }

    /**
     * @param array<int, mixed> $parameters
     * @param ConditionDefinition $def
     * @return void
     */
    protected function addParametersForCondition(array &$parameters, ConditionDefinition $def): void
    {
        while ($def !== null) {
            $value = $def->value;
            match (true) {
                is_iterable($value) => array_push($parameters, ...iterator_to_array($value)),
                $value instanceof Expression => array_push($parameters, ...$value->generateParameters($this)),
                $value instanceof QueryStatement => array_push($parameters, ...$value->generateParameters($this)),
                default => $parameters[] = $value,
            };
            $def = $def->next;
        }
    }

    /**
     * @param iterable<array-key, mixed> $parameters
     * @return array<mixed>
     */
    protected function stringifyValues(iterable $parameters): array
    {
        $strings = [];
        foreach ($parameters as $name => $parameter) {
            $strings[$name] = $this->stringifyValue($parameter);
        }
        return $strings;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    protected function stringifyValue(mixed $value): mixed
    {
        if (is_iterable($value)) {
            return Json::encode(iterator_to_array($value));
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format($this->dateTimeFormat);
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        return $value;
    }

    /**
     * @param ListTablesStatement $statement
     * @return string
     */
    public function prepareTemplateForListTables(ListTablesStatement $statement): string
    {
        $database = $this->asLiteral($this->connectionConfig->getTableSchema());
        return "SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = {$database}";
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
     * @param iterable<int, stdClass> $rows
     * @return Iterator<int, stdClass>
     */
    abstract public function normalizeListColumns(iterable $rows): Iterator;

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
