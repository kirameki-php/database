<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Syntax;

use BackedEnum;
use DateTimeInterface;
use Kirameki\Core\Exceptions\UnreachableException;
use Kirameki\Core\Json;
use Kirameki\Core\Value;
use Kirameki\Database\Adapters\DatabaseConfig;
use Kirameki\Database\Query\Expressions\Column;
use Kirameki\Database\Query\Expressions\Expression;
use Kirameki\Database\Query\Statements\ConditionDefinition;
use Kirameki\Database\Query\Statements\ConditionsStatement;
use Kirameki\Database\Query\Statements\DeleteStatement;
use Kirameki\Database\Query\Statements\InsertStatement;
use Kirameki\Database\Query\Statements\JoinDefinition;
use Kirameki\Database\Query\Statements\QueryStatement;
use Kirameki\Database\Query\Statements\SelectBuilder;
use Kirameki\Database\Query\Statements\SelectStatement;
use Kirameki\Database\Query\Statements\UpdateStatement;
use Kirameki\Database\Query\Support\LockOption;
use Kirameki\Database\Query\Support\LockType;
use Kirameki\Database\Query\Support\Operator;
use Kirameki\Database\Query\Support\Range;
use Kirameki\Database\Syntax;
use RuntimeException;
use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
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
     * FOR DEBUGGING ONLY
     *
     * @param QueryStatement $statement
     * @return string
     */
    public function interpolate(QueryStatement $statement): string
    {
        $parameters = $this->stringifyParameters($statement->getParameters());
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
        }, $statement->prepare());
    }

    /**
     * @param SelectStatement $statement
     * @return string
     */
    public function formatSelectStatement(SelectStatement $statement): string
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
    public function prepareParametersForSelect(SelectStatement $statement): array
    {
        return $this->stringifyParameters($this->getParametersForConditions($statement));
    }

    /**
     * @param InsertStatement $statement
     * @return string
     */
    public function formatInsertStatement(InsertStatement $statement): string
    {
        if ($statement->dataset === []) {
            return "INSERT INTO {$this->asIdentifier($statement->table)} DEFAULT VALUES";
        }

        return implode(' ', array_filter([
            'INSERT INTO',
            $this->asIdentifier($statement->table),
            $this->formatInsertColumnsPart($statement),
            'VALUES',
            $this->formatInsertValuesPart($statement),
            $this->formatReturningPart($statement),
        ]));
    }

    /**
     * @param InsertStatement $statement
     * @return array<mixed>
     */
    public function prepareParametersForInsert(InsertStatement $statement): array
    {
        $columns = $statement->columns();
        $parameters = [];
        foreach ($statement->dataset as $data) {
            if (!is_array($data)) {
                throw new RuntimeException('Data should be an array but ' . Value::getType($data) . ' given.');
            }
            foreach ($columns as $column) {
                $parameters[] = $data[$column] ?? null;
            }
        }

        return $this->stringifyParameters($parameters);
    }

    /**
     * @param UpdateStatement $statement
     * @return string
     */
    public function formatUpdateStatement(UpdateStatement $statement): string
    {
        return implode(' ', array_filter([
            'UPDATE',
            $this->asIdentifier($statement->table),
            'SET',
            $this->formatUpdateAssignmentsPart($statement),
            $this->formatConditionsPart($statement),
            $this->formatReturningPart($statement),
        ]));
    }

    /**
     * @param UpdateStatement $statement
     * @return string
     */
    protected function formatUpdateAssignmentsPart(UpdateStatement $statement): string
    {
        $columns = array_keys($statement->data);
        $assignments = array_map(fn(string $column): string => "{$this->asIdentifier($column)} = ?", $columns);
        return $this->asCsv($assignments);
    }

    /**
     * @param UpdateStatement $statement
     * @return array<mixed>
     */
    public function prepareParametersForUpdate(UpdateStatement $statement): array
    {
        $parameters = array_merge($statement->data, $this->getParametersForConditions($statement));
        return $this->stringifyParameters($parameters);
    }

    /**
     * @param DeleteStatement $statement
     * @return string
     */
    public function formatDeleteStatement(DeleteStatement $statement): string
    {
        return implode(' ', array_filter([
            'DELETE FROM',
            $this->asIdentifier($statement->table),
            $this->formatConditionsPart($statement),
            $this->formatReturningPart($statement),
        ]));
    }

    /**
     * @param DeleteStatement $statement
     * @return array<mixed>
     */
    public function prepareParametersForDelete(DeleteStatement $statement): array
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
        return match ($statement->lockType) {
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
        return match ($statement->lockOption) {
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
        foreach ($statement->tables as $table) {
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
     * @param InsertStatement $statement
     * @return string
     */
    protected function formatInsertColumnsPart(InsertStatement $statement): string
    {
        return $this->asEnclosedCsv(
            array_map(
                fn(string $column): string => $this->asIdentifier($column),
                $statement->columns(),
            ),
        );
    }

    /**
     * @param InsertStatement $statement
     * @return string
     */
    protected function formatInsertValuesPart(InsertStatement $statement): string
    {
        $listSize = count($statement->dataset);
        $columnCount = count($statement->columns());
        $placeholders = [];
        for ($i = 0; $i < $listSize; $i++) {
            $binders = [];
            for ($j = 0; $j < $columnCount; $j++) {
                $binders[] = '?';
            }
            $placeholders[] = $this->asEnclosedCsv($binders);
        }
        return $this->asCsv($placeholders);
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
                $value instanceof SelectBuilder => $this->formatSubQuery($value),
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

        if ($value instanceof SelectBuilder) {
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

        if ($value instanceof SelectBuilder) {
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
     * @param SelectBuilder $builder
     * @return string
     */
    protected function formatSubQuery(SelectBuilder $builder): string
    {
        return '(' . $builder->getStatement()->prepare() . ')';
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
        $clause = [];
        foreach ($statement->orderBy as $column => $sort) {
            $clause[] = $this->asColumn($column) . ' ' . $sort->value;
        }
        return "ORDER BY {$this->asCsv($clause)}";
    }

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
     * @param InsertStatement|UpdateStatement|DeleteStatement $statement
     * @return string
     */
    protected function formatReturningPart(InsertStatement|UpdateStatement|DeleteStatement $statement): string
    {
        if ($statement->returning === null) {
            return '';
        }

        $columns = array_map($this->asIdentifier(...), $statement->returning);

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
            if ($value instanceof SelectBuilder) {
                $value = $value->getStatement();
            }

            if (is_iterable($value)) {
                foreach ($value as $parameter) {
                    $parameters[] = $parameter;
                }
            } elseif ($value instanceof Expression || $value instanceof QueryStatement) {
                foreach ($value->getParameters() as $parameter) {
                    $parameters[] = $parameter;
                }
            } else {
                $parameters[] = $value;
            }
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
}
