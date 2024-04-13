<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Syntax;

use BackedEnum;
use DateTimeInterface;
use Iterator;
use Kirameki\Collections\Utils\Arr;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Core\Exceptions\UnreachableException;
use Kirameki\Core\Json;
use Kirameki\Core\Value;
use Kirameki\Database\Adapters\DatabaseConfig;
use Kirameki\Database\Info\Statements\ListColumnsStatement;
use Kirameki\Database\Info\Statements\ListIndexesStatement;
use Kirameki\Database\Info\Statements\ListTablesStatement;
use Kirameki\Database\Query\Expressions\Column;
use Kirameki\Database\Query\Expressions\Expression;
use Kirameki\Database\Query\Statements\ConditionDefinition;
use Kirameki\Database\Query\Statements\ConditionsStatement;
use Kirameki\Database\Query\Statements\DeleteStatement;
use Kirameki\Database\Query\Statements\QueryExecutable;
use Kirameki\Database\Query\Statements\InsertStatement;
use Kirameki\Database\Query\Statements\JoinDefinition;
use Kirameki\Database\Query\Statements\QueryStatement;
use Kirameki\Database\Query\Statements\SelectStatement;
use Kirameki\Database\Query\Statements\UpdateStatement;
use Kirameki\Database\Query\Statements\UpsertStatement;
use Kirameki\Database\Query\Support\LockOption;
use Kirameki\Database\Query\Support\LockType;
use Kirameki\Database\Query\Support\Operator;
use Kirameki\Database\Query\Support\Ordering;
use Kirameki\Database\Query\Support\Range;
use Kirameki\Database\Query\Support\SortOrder;
use Kirameki\Database\Query\Support\Tags;
use Kirameki\Database\Query\Support\TagsFormat;
use Kirameki\Database\Syntax;
use RuntimeException;
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
     * @param DatabaseConfig $config
     * @param string $identifierDelimiter
     * @param string $literalDelimiter
     * @param string $dateTimeFormat
     */
    public function __construct(
        DatabaseConfig $config,
        string $identifierDelimiter,
        string $literalDelimiter,
        protected readonly string $dateTimeFormat,
    )
    {
        parent::__construct($config, $identifierDelimiter, $literalDelimiter);
    }

    /**
     * @template TQueryStatement of QueryStatement
     * @param TQueryStatement $statement
     * @param string $template
     * @param list<mixed> $parameters
     * @return QueryExecutable<TQueryStatement>
     */
    public function toExecutable(QueryStatement $statement, string $template, array $parameters = []): QueryExecutable
    {
        $template .= $this->formatTags($statement->tags);
        return new QueryExecutable($statement, $template, $parameters);
    }

    /**
     * FOR DEBUGGING ONLY
     *
     * @param QueryExecutable<QueryStatement> $executable
     * @return string
     */
    public function interpolate(QueryExecutable $executable): string
    {
        $parameters = $this->stringifyParameters($executable->parameters);
        $remains = count($parameters);

        return (string) preg_replace_callback('/\?\??/', function($matches) use (&$parameters, &$remains) {
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
        }, $executable->template);
    }

    /**
     * @param SelectStatement $statement
     * @return QueryExecutable<SelectStatement>
     */
    public function compileSelect(SelectStatement $statement): QueryExecutable
    {
        $template = $this->prepareTemplateForSelect($statement);
        $parameters = $this->prepareParametersForSelect($statement);
        return $this->toExecutable($statement, $template, $parameters);
    }

    /**
     * @param SelectStatement $statement
     * @return string
     */
    protected function prepareTemplateForSelect(SelectStatement $statement): string
    {
        return implode(' ', array_filter([
            $this->formatSelectPart($statement),
            $this->formatFromPart($statement),
            $this->formatJoinPart($statement),
            $this->formatWherePart($statement),
            $this->formatGroupByPart($statement),
            $this->formatOrderByPart($statement),
            $this->formatLimitPart($statement),
            $this->formatOffsetPart($statement),
        ]));
    }

    /**
     * @param SelectStatement $statement
     * @return array<mixed>
     */
    protected function prepareParametersForSelect(SelectStatement $statement): array
    {
        return $this->stringifyParameters($this->getParametersForConditions($statement));
    }

    /**
     * @param InsertStatement $statement
     * @return QueryExecutable<InsertStatement>
     */
    public function compileInsert(InsertStatement $statement): QueryExecutable
    {
        $columnsMap = [];
        foreach ($statement->dataset as $data) {
            foreach (array_keys($data) as $name) {
                $columnsMap[$name] = null;
            }
        }
        $columns = array_keys($columnsMap);

        $template = $this->prepareTemplateForInsert($statement, $columns);
        $parameters = $this->formatDatasetParameters($statement->dataset, $columns);
        return $this->toExecutable($statement, $template, $parameters);
    }

    /**
     * @param InsertStatement $statement
     * @param list<string> $columns
     * @return string
     */
    protected function prepareTemplateForInsert(InsertStatement $statement, array $columns): string
    {
        if ($columns === []) {
            return "INSERT INTO {$this->asIdentifier($statement->table)} DEFAULT VALUES";
        }

        return implode(' ', array_filter([
            'INSERT INTO',
            $this->asIdentifier($statement->table),
            $this->formatDatasetColumnsPart($columns),
            'VALUES',
            $this->formatDatasetValuesPart($statement->dataset, $columns),
            $this->formatReturningPart($statement->returning),
        ]));
    }

    /**
     * @param UpsertStatement $statement
     * @return QueryExecutable<UpsertStatement>
     */
    public function compileUpsert(UpsertStatement $statement): QueryExecutable
    {
        $columnsMap = [];
        foreach ($statement->dataset as $data) {
            foreach (array_keys($data) as $name) {
                $columnsMap[$name] = null;
            }
        }
        $columns = array_keys($columnsMap);

        $template = $this->prepareTemplateForUpsert($statement, $columns);
        $parameters = $this->formatDatasetParameters($statement->dataset, $columns);
        return $this->toExecutable($statement, $template, $parameters);
    }

    /**
     * @param UpsertStatement $statement
     * @param list<string> $columns
     * @return string
     */
    protected function prepareTemplateForUpsert(UpsertStatement $statement, array $columns): string
    {
        return implode(' ', array_filter([
            'INSERT INTO',
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
     * @param UpdateStatement $statement
     * @return QueryExecutable<UpdateStatement>
     */
    public function compileUpdate(UpdateStatement $statement): QueryExecutable
    {
        $template = $this->prepareTemplateForUpdate($statement);
        $parameters = $this->prepareParametersForUpdate($statement);
        return $this->toExecutable($statement, $template, $parameters);
    }

    /**
     * @param UpdateStatement $statement
     * @return string
     */
    protected function prepareTemplateForUpdate(UpdateStatement $statement): string
    {
        return implode(' ', array_filter([
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
        return $this->stringifyParameters($parameters);
    }

    /**
     * @param DeleteStatement $statement
     * @return QueryExecutable<DeleteStatement>
     */
    public function compileDelete(DeleteStatement $statement): QueryExecutable
    {
        $template = $this->prepareTemplateForDelete($statement);
        $parameters = $this->prepareParametersForDelete($statement);
        return $this->toExecutable($statement, $template, $parameters);
    }

    /**
     * @param DeleteStatement $statement
     * @return string
     */
    protected function prepareTemplateForDelete(DeleteStatement $statement): string
    {
        return implode(' ', array_filter([
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
    protected function prepareParametersForDelete(DeleteStatement $statement): array
    {
        return $this->stringifyParameters($this->getParametersForConditions($statement));
    }

    /**
     * @param SelectStatement $statement
     * @return string
     */
    protected function formatSelectPart(SelectStatement $statement): string
    {
        return implode(' ', array_filter([
            'SELECT',
            $statement->distinct ? 'DISTINCT' : null,
            $this->formatSelectColumnsPart($statement),
            $this->formatSelectLockPart($statement),
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
                ? $column->prepare($this)
                : $this->asColumn($column);
        }, $columns));
    }

    /**
     * @param SelectStatement $statement
     * @return string
     */
    protected function formatSelectLockPart(SelectStatement $statement): string
    {
        return match ($statement->lock?->type) {
            LockType::Exclusive => 'FOR UPDATE' . $this->formatSelectLockOptionPart($statement),
            LockType::Shared => 'FOR SHARE',
            null => '',
        };
    }

    /**
     * @param SelectStatement $statement
     * @return string
     */
    protected function formatSelectLockOptionPart(SelectStatement $statement): string
    {
        return match ($statement->lock?->option) {
            LockOption::Nowait => ' NOWAIT',
            LockOption::SkipLocked => ' SKIP LOCKED',
            null => '',
        };
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
                ? $table->prepare($this)
                : $this->asTable($table);
        }
        if (count($expressions) === 0) {
            return '';
        }
        return implode(' ', array_filter([
            'FROM',
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
            $expr .= 'ON ' . $this->formatCondition($def->condition);
            return $expr;
        }, $joins));
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
     * @param list<array<string, mixed>> $dataset
     * @param list<string> $columns
     * @return string
     */
    protected function formatDatasetValuesPart(array $dataset, array $columns): string
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
     * @param list<array<string, mixed>> $dataset
     * @param list<string> $columns
     * @return array<mixed>
     */
    protected function formatDatasetParameters(array $dataset, array $columns): array
    {
        $parameters = [];
        foreach ($dataset as $data) {
            if (!is_array($data)) {
                throw new RuntimeException('Data should be an array but ' . Value::getType($data) . ' given.');
            }
            foreach ($columns as $column) {
                if (array_key_exists($column, $data)) {
                    $parameters[] = $data[$column];
                }
            }
        }
        return $this->stringifyParameters($parameters);
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
        $clause = 'ON CONFLICT';
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
        $columns = array_map(fn(string $column): string => "{$column} = EXCLUDED.{$column}", $columns);
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
            $this->formatOrderByPart($statement),
            $this->formatLimitPart($statement),
        ]));
    }

    /**
     * @param ConditionsStatement $statement
     * @return string
     */
    protected function formatWherePart(ConditionsStatement $statement): string
    {
        if ($statement->where === null) {
            return '';
        }

        $clauses = [];
        foreach ($statement->where as $def) {
            $clauses[] = ($def->next !== null)
                ? '(' . $this->formatCondition($def) . ')'
                : $this->formatCondition($def);
        }

        return 'WHERE ' . implode(' AND ', $clauses);
    }

    /**
     * @param ConditionDefinition $def
     * @return string
     */
    protected function formatCondition(ConditionDefinition $def): string
    {
        $parts = [];
        $parts[] = $this->formatConditionSegment($def);

        // Dig through all chained clauses if exists
        while (($logic = $def->nextLogic) && ($def = $def->next)) {
            $parts[] = $logic . ' ' . $this->formatConditionSegment($def);
        }

        return implode(' ', $parts);
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
            default => throw new RuntimeException('Unknown Operator: ' . Value::getType($def->operator?->value)),
        };
    }

    /**
     * @param ConditionDefinition $def
     * @return string
     */
    protected function formatConditionForRaw(ConditionDefinition $def): string
    {
        if ($def->value instanceof Expression) {
            return $def->value->prepare($this);
        }

        throw new RuntimeException('Unknown condition:' . Value::getType($def->value));
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
                $value instanceof QueryExecutable => $this->formatSubQuery($value),
                $value instanceof Expression => $value->prepare($this),
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

        if ($value instanceof QueryExecutable) {
            $subQuery = $this->formatSubQuery($value);
            return "{$column} {$operator} {$subQuery}";
        }

        throw new RuntimeException('Unknown condition');
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

        if ($value instanceof QueryExecutable) {
            $subQuery = $this->formatSubQuery($value);
            return "{$column} {$operator} {$subQuery}";
        }

        throw new RuntimeException('Unknown condition');
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

        throw new RuntimeException('Unknown condition');
    }

    /**
     * @param QueryExecutable<QueryStatement> $executable
     * @return string
     */
    protected function formatSubQuery(QueryExecutable $executable): string
    {
        return '(' . $executable->template . ')';
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
        $clause = [];
        foreach ($statement->groupBy as $column) {
            $clause[] = $this->asColumn($column);
        }
        return "GROUP BY {$this->asCsv($clause)}";
    }

    /**
     * @param ConditionsStatement $statement
     * @return string
     */
    protected function formatOrderByPart(ConditionsStatement $statement): string
    {
        if ($statement->orderBy === null) {
            return '';
        }
        $clauses = [];
        foreach ($statement->orderBy as $column => $ordering) {
            $clauses[] = implode(' ', array_filter([
                $this->asIdentifier($column),
                $this->formatSortOrderingPart($column, $ordering),
                $this->formatNullOrderingPart($column, $ordering),
            ]));
        }
        return "ORDER BY {$this->asCsv($clauses)}";
    }

    /**
     * @param string $column
     * @param Ordering $ordering
     * @return string
     */
    protected function formatSortOrderingPart(string $column, Ordering $ordering): string
    {
        return match ($ordering->sort) {
            SortOrder::Ascending => 'ASC',
            SortOrder::Descending => 'DESC',
        };
    }

    /**
     * @param string $column
     * @param Ordering $ordering
     * @return string
     */
    abstract protected function formatNullOrderingPart(string $column, Ordering $ordering): string;

    /**
     * @param ConditionsStatement $statement
     * @return string
     */
    protected function formatLimitPart(ConditionsStatement $statement): string
    {
        return $statement->limit !== null
            ? "LIMIT {$statement->limit}"
            : '';
    }

    /**
     * @param SelectStatement $statement
     * @return string
     */
    protected function formatOffsetPart(SelectStatement $statement): string
    {
        return $statement->offset !== null
            ? "OFFSET {$statement->offset}"
            : '';
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
        return match($this->config->getTagFormat()) {
            TagsFormat::Default => $this->formatTagsForLogs($tags),
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
            return $column->prepare($this);
        }

        throw new RuntimeException('Column name expected but null given');
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
                $value instanceof Expression => array_push($parameters, ...$value->getParameters()),
                $value instanceof QueryExecutable => array_push($parameters, ...$value->parameters),
                default => $parameters[] = $value,
            };
            $def = $def->next;
        }
    }

    /**
     * @param array<scalar> $values
     * @return string
     */
    protected function asEnclosedCsv(array $values): string
    {
        return '(' . $this->asCsv($values) . ')';
    }

    /**
     * @param array<scalar> $values
     * @return string
     */
    protected function asCsv(array $values): string
    {
        return implode(', ', $values);
    }

    /**
     * @param iterable<array-key, mixed> $parameters
     * @return array<mixed>
     */
    protected function stringifyParameters(iterable $parameters): array
    {
        $strings = [];
        foreach ($parameters as $name => $parameter) {
            $strings[$name] = $this->stringifyParameter($parameter);
        }
        return $strings;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    protected function stringifyParameter(mixed $value): mixed
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
     * @return QueryExecutable<ListTablesStatement>
     */
    public function compileListTables(ListTablesStatement $statement): QueryExecutable
    {
        $database = $this->asLiteral($this->config->getDatabase());
        $template = "SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = {$database}";
        return $this->toExecutable($statement, $template);
    }

    /**
     * @param ListColumnsStatement $statement
     * @return QueryExecutable<ListColumnsStatement>
     */
    public function compileListColumns(ListColumnsStatement $statement): QueryExecutable
    {
        $columns = implode(', ', [
            "COLUMN_NAME AS `name`",
            "DATA_TYPE AS `type`",
            "IS_NULLABLE AS `nullable`",
            "ORDINAL_POSITION AS `position`",
        ]);
        $database = $this->asLiteral($this->config->getDatabase());
        $table = $this->asLiteral($statement->table);
        $template = implode(' ', [
            "SELECT {$columns} FROM INFORMATION_SCHEMA.COLUMNS",
            "WHERE TABLE_SCHEMA = {$database}",
            "AND TABLE_NAME = {$table}",
            "ORDER BY ORDINAL_POSITION ASC",
        ]);
        return $this->toExecutable($statement, $template);
    }

    /**
     * @param iterable<int, stdClass> $rows
     * @return Iterator<int, stdClass>
     */
    public function normalizeListColumns(iterable $rows): Iterator
    {
        foreach ($rows as $row) {
            $row->type = match ($row->type) {
                'int', 'mediumint', 'tinyint', 'smallint', 'bigint' => 'integer',
                'decimal', 'float', 'double' => 'float',
                'bool' => 'bool',
                'varchar' => 'string',
                'datetime' => 'datetime',
                'json' => 'json',
                'blob' => 'binary',
                default => throw new LogicException('Unsupported column type: ' . $row->type, [
                    'type' => $row->type,
                ]),
            };
            $row->nullable = $row->nullable === 'YES';
            yield $row;
        }
    }

    /**
     * @param ListIndexesStatement $statement
     * @return QueryExecutable<ListIndexesStatement>
     */
    public function compileListIndexes(ListIndexesStatement $statement): QueryExecutable
    {
        $columns = implode(', ', [
            "INDEX_NAME AS `name`",
            "CASE WHEN `INDEX_NAME` = 'PRIMARY' THEN 'primary' WHEN `NON_UNIQUE` = 0 THEN 'unique' ELSE 'index' END AS `type`",
            "group_concat(COLUMN_NAME) AS `columns`",
        ]);
        $database = $this->asLiteral($this->config->getDatabase());
        $table = $this->asLiteral($statement->table);
        return $this->toExecutable($statement, implode(' ', [
            "SELECT {$columns} FROM INFORMATION_SCHEMA.STATISTICS",
            "WHERE TABLE_SCHEMA = {$database}",
            "AND TABLE_NAME = {$table}",
            "GROUP BY INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX",
            "ORDER BY INDEX_NAME ASC, SEQ_IN_INDEX ASC",
        ]));
    }
}
