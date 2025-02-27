<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Syntax;

use Kirameki\Collections\Utils\Arr;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Core\Exceptions\NotSupportedException;
use Kirameki\Core\Func;
use Kirameki\Core\Value;
use Kirameki\Database\Exceptions\DropProtectionException;
use Kirameki\Database\Expression;
use Kirameki\Database\Info\Statements\ListColumnsStatement;
use Kirameki\Database\Info\Statements\ListForeignKeysStatement;
use Kirameki\Database\Info\Statements\ListIndexesStatement;
use Kirameki\Database\Info\Statements\ListTablesStatement;
use Kirameki\Database\Info\Statements\TableExistsStatement;
use Kirameki\Database\Query\Expressions\QueryFunction;
use Kirameki\Database\Query\Statements\Bounds;
use Kirameki\Database\Query\Statements\CompoundDefinition;
use Kirameki\Database\Query\Statements\ConditionDefinition;
use Kirameki\Database\Query\Statements\ConditionsStatement;
use Kirameki\Database\Query\Statements\Dataset;
use Kirameki\Database\Query\Statements\DeleteStatement;
use Kirameki\Database\Query\Statements\InsertStatement;
use Kirameki\Database\Query\Statements\JoinDefinition;
use Kirameki\Database\Query\Statements\Lock;
use Kirameki\Database\Query\Statements\LockOption;
use Kirameki\Database\Query\Statements\LockType;
use Kirameki\Database\Query\Statements\Logic;
use Kirameki\Database\Query\Statements\Operator;
use Kirameki\Database\Query\Statements\Ordering;
use Kirameki\Database\Query\Statements\QueryStatement;
use Kirameki\Database\Query\Statements\RawStatement;
use Kirameki\Database\Query\Statements\SelectStatement;
use Kirameki\Database\Query\Statements\SortOrder;
use Kirameki\Database\Query\Statements\Tags;
use Kirameki\Database\Query\Statements\TagsFormat;
use Kirameki\Database\Query\Statements\UpdateStatement;
use Kirameki\Database\Query\Statements\UpsertStatement;
use Kirameki\Database\Query\Statements\WithDefinition;
use Kirameki\Database\Syntax;
use stdClass;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_push;
use function count;
use function current;
use function dump;
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
        $parameters = $this->stringifyParameters($parameters);
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
            $this->formatSelectLockPart($statement->lock),
        ]);

        return $this->concat([
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
            $this->formatTags($statement->tags),
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
            $this->formatTags($statement->tags),
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
            $this->formatTags($statement->tags),
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
        $this->addParametersForWhere($parameters, $statement);
        return $this->stringifyParameters($parameters);
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
        return $this->stringifyParameters($statement->parameters);
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
        $glue = ' ' . Logic::And->value . ' ';
        $conditions = $statement->where;

        return $conditions !== null
            ? 'WHERE ' . implode($glue, array_map($this->formatConditionDefinition(...), $conditions))
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
            $parts[] = $logic->value . ' ' . $this->formatConditionSegment($def);
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

        $message = 'Invalid Raw value. Expected: Expression. Got: ' . Value::getType($def->value) . '.';
        throw new NotSupportedException($message, [
            'definition' => $def,
        ]);
    }

    /**
     * @param ConditionDefinition $def
     * @return string
     */
    protected function formatConditionForEqual(ConditionDefinition $def): string
    {
        $column = $this->asColumn($def->column);
        $negated = $def->negated;
        $value = $def->value;

        return $value !== null
            ? $this->formatConditionForOperator($column, $negated ? '!=' : '=', $value)
            : $this->formatConditionForNull($column, $negated);
    }

    /**
     * @param ConditionDefinition $def
     * @return string
     */
    protected function formatConditionForLessThanOrEqualTo(ConditionDefinition $def): string
    {
        $column = $this->asColumn($def->column);
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
        $column = $this->asColumn($def->column);
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
        $column = $this->asColumn($def->column);
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
        $column = $this->asColumn($def->column);
        $operator = $def->negated ? '<=' : '>';
        $value = $def->value;
        return $this->formatConditionForOperator($column, $operator, $value);
    }

    /**
     * @param ConditionDefinition $def
     * @return string
     */
    protected function formatConditionForIn(ConditionDefinition $def): string
    {
        $column = $this->asColumn($def->column);
        $operator = ($def->negated ? Logic::Not->value . ' ' : '') . $def->operator->value;
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

        $message = 'Value for WHERE ' . $operator . '. ';
        $message .= 'Expected: iterable|SelectStatement. Got: ' . Value::getType($value) . '.';
        throw new NotSupportedException($message, [
            'definition' => $def,
        ]);
    }

    /**
     * @param ConditionDefinition $def
     * @return string
     */
    protected function formatConditionForBetween(ConditionDefinition $def): string
    {
        $column = $this->asColumn($def->column);
        $operator = ($def->negated ? Logic::Not->value . ' ' : '') . $def->operator->value;
        $min = $this->asPlaceholder($def->value[0]);
        $max = $this->asPlaceholder($def->value[1]);
        $logic = Logic::And->value;
        return "{$column} {$operator} {$min} {$logic} {$max}";
    }

    /**
     * @param ConditionDefinition $def
     * @return string
     */
    protected function formatConditionForExists(ConditionDefinition $def): string
    {
        $operator = ($def->negated ? Logic::Not->value . ' ' : '') . $def->operator->value;
        $value = $def->value;

        if ($value instanceof QueryStatement) {
            return "{$operator} {$this->formatSubQuery($value)}";
        }

        $message = 'Value for WHERE ' . $operator . '. ';
        $message .= 'Expected: SelectStatement. Got: ' . Value::getType($value) . '.';
        throw new NotSupportedException($message, [
            'definition' => $def,
        ]);
    }

    /**
     * @param ConditionDefinition $def
     * @return string
     */
    protected function formatConditionForLike(ConditionDefinition $def): string
    {
        $column = $this->asColumn($def->column);
        $operator = ($def->negated ? Logic::Not->value . ' ' : '') . $def->operator->value;
        $value = $def->value;
        return $this->formatConditionForOperator($column, $operator, $value);
    }

    /**
     * @param ConditionDefinition $def
     * @return string
     */
    protected function formatConditionForRange(ConditionDefinition $def): string
    {
        $column = $this->asColumn($def->column);
        $negated = $def->negated;
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
        $message .= 'Expected: Bounds. Got: ' . Value::getType($value) . '.';
        throw new NotSupportedException($message, [
            'definition' => $def,
        ]);
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
     * @param string $column
     * @param bool $negated
     * @return string
     */
    protected function formatConditionForNull(string $column, bool $negated): string
    {
        return $column . ' IS ' . ($negated ? Logic::Not->value . ' ' : '') . 'NULL';
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
        $glue = ' ' . Logic::And->value . ' ';
        $conditions = $statement->having;

        return $conditions !== null
            ? 'HAVING ' . implode($glue, array_map($this->formatConditionDefinition(...), $conditions))
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

        return "RETURNING {$this->asCsv($this->asColumns($columns))}";
    }

    /**
     * @param QueryFunction $func
     * @return string
     */
    public function formatFunction(QueryFunction $func): string
    {
        return $this->concat([
            $this->formatFunctionNamePart($func),
            $this->formatWindowFunctionPart($func),
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
     * @param QueryFunction $func
     * @return string
     */
    protected function formatWindowFunctionPart(QueryFunction $func): string
    {
        if (!$func->isWindowFunction) {
            return '';
        }

        $parts = [];
        if ($func->partitionBy) {
            $parts[] = 'PARTITION BY ' . $this->asCsv($this->asIdentifiers($func->partitionBy));
        }
        if ($func->orderBy !== null) {
            $clauses = [];
            foreach ($func->orderBy as $column => $ordering) {
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
        if ($tags === null || count($tags) === 0) {
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
        return $this->asBlockComment(implode(',', $fields));
    }

    /**
     * @param Tags $tags
     * @return string
     */
    protected function formatTagsForOpenTelemetry(Tags $tags): string
    {
        $fields = Arr::map($tags, static fn(mixed $v, string $k) => rawurlencode($k) . "='" . rawurlencode((string) $v) . "'");
        return $this->asBlockComment(implode(',', $fields));
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
