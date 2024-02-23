<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Formatters;

use BackedEnum;
use DateTimeInterface;
use Kirameki\Database\Query\Builders\SelectBuilder;
use Kirameki\Database\Query\Expressions\Column;
use Kirameki\Database\Query\Expressions\Expr;
use Kirameki\Database\Query\Statements\ConditionDefinition;
use Kirameki\Database\Query\Statements\ConditionsStatement;
use Kirameki\Database\Query\Statements\DeleteStatement;
use Kirameki\Database\Query\Statements\InsertStatement;
use Kirameki\Database\Query\Statements\JoinDefinition;
use Kirameki\Database\Query\Statements\SelectStatement;
use Kirameki\Database\Query\Statements\UpdateStatement;
use Kirameki\Database\Query\Support\LockOption;
use Kirameki\Database\Query\Support\LockType;
use Kirameki\Database\Query\Support\Operator;
use Kirameki\Database\Query\Support\Range;
use Kirameki\Support\Json;
use Kirameki\Support\Str;
use RuntimeException;
use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function count;
use function current;
use function implode;
use function is_array;
use function is_bool;
use function is_iterable;
use function is_null;
use function is_string;
use function next;
use function preg_match;
use function preg_quote;
use function preg_replace_callback;

abstract class Formatter
{
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
    public function formatBindingsForSelect(SelectStatement $statement): array
    {
        return $this->parameterizeBindings($this->getBindingsForConditions($statement));
    }

    /**
     * @param InsertStatement $statement
     * @return string
     */
    public function formatInsertStatement(InsertStatement $statement): string
    {
        return implode(' ', array_filter([
            'INSERT INTO',
            $this->quote($statement->table),
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
    public function formatBindingsForInsert(InsertStatement $statement): array
    {
        $columns = $statement->columns();
        $bindings = [];
        foreach ($statement->dataset as $data) {
            if (!is_array($data)) {
                throw new RuntimeException('Data should be an array but ' . Str::typeOf($data) . ' given.');
            }
            foreach ($columns as $column) {
                $bindings[] = $data[$column] ?? null;
            }
        }

        return $this->parameterizeBindings($bindings);
    }

    /**
     * @param UpdateStatement $statement
     * @return string
     */
    public function formatUpdateStatement(UpdateStatement $statement): string
    {
        return implode(' ', array_filter([
            'UPDATE',
            $this->quote($statement->table),
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
        $marker = $this->getParameterMarker();
        $assignments = array_map(fn(string $column): string => $this->quote($column) . ' = ' . $marker, $columns);
        return $this->asCsv($assignments);
    }

    /**
     * @param UpdateStatement $statement
     * @return array<mixed>
     */
    public function formatBindingsForUpdate(UpdateStatement $statement): array
    {
        $bindings = array_merge($statement->data, $this->getBindingsForConditions($statement));
        return $this->parameterizeBindings($bindings);
    }

    /**
     * @param DeleteStatement $statement
     * @return string
     */
    public function formatDeleteStatement(DeleteStatement $statement): string
    {
        return implode(' ', array_filter([
            'DELETE FROM',
            $this->quote($statement->table),
            $this->formatConditionsPart($statement),
            $this->formatReturningPart($statement),
        ]));
    }

    /**
     * @param DeleteStatement $statement
     * @return array<mixed>
     */
    public function formatBindingsForDelete(DeleteStatement $statement): array
    {
        return $this->parameterizeBindings($this->getBindingsForConditions($statement));
    }

    /**
     * FOR DEBUGGING ONLY
     *
     * @param string $statement
     * @param array<mixed> $bindings
     * @return string
     */
    public function interpolate(string $statement, array $bindings): string
    {
        $remains = count($bindings);
        return (string) preg_replace_callback('/\?\??/', function ($matches) use (&$bindings, &$remains) {
            if ($matches[0] === '?' && $remains > 0) {
                $current = current($bindings);
                next($bindings);
                $remains--;
                return match (true) {
                    is_null($current) => 'NULL',
                    is_bool($current) => $current ? 'TRUE' : 'FALSE',
                    is_string($current) => $this->literalize($current),
                    default => $current,
                };
            }
            return $matches[0];
        }, $statement);
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

        return $this->asCsv(array_map(function (string|Expr $column): string {
            return ($column instanceof Expr)
                ? $column->prepare($this)
                : $this->columnize($column);
        }, $columns));
    }

    /**
     * @param SelectStatement $statement
     * @return string
     */
    protected function formatSelectLockPart(SelectStatement $statement): string
    {
        return match ($statement->lockType) {
            LockType::Exclusive => 'FOR UPDATE'.$this->formatSelectLockOptionPart($statement),
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
            $expressions[]= ($table instanceof Expr)
                ? $table->prepare($this)
                : $this->tableize($table);
        }

        return (count($expressions) > 0)
            ? 'FROM ' . $this->asCsv($expressions)
            : '';
    }

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

        return implode(' ', array_map(function (JoinDefinition $def): string {
            $expr = $def->type->value . ' ';
            $expr.= $this->tableize($def->table) . ' ';
            $expr.= 'ON ' . $this->formatCondition($def->condition);
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
                fn(string $column): string => $this->quote($column),
                $statement->columns()
            )
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
        $marker = $this->getParameterMarker();
        $placeholders = [];
        for ($i = 0; $i < $listSize; $i++) {
            $binders = [];
            for ($j = 0; $j < $columnCount; $j++) {
                $binders[] = $marker;
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
            default => throw new RuntimeException('Unknown Operator: '.Str::valueOf($def->operator?->value)),
        };
    }

    /**
     * @param ConditionDefinition $def
     * @return string
     */
    protected function formatConditionForRaw(ConditionDefinition $def): string
    {
        if ($def->value instanceof Expr) {
            return $def->value->prepare($this);
        }

        throw new RuntimeException('Unknown condition:' . Str::valueOf($def->value));
    }

    /**
     * @param ConditionDefinition $def
     * @return string
     */
    protected function formatConditionForEqual(ConditionDefinition $def): string
    {
        $column = $this->getDefinedColumn($def);
        $operator = $def->negated ? '!=': '=';
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
            $value instanceof Expr => $value->prepare($this),
            default => $this->getParameterMarker(),
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
        $marker = $this->getParameterMarker();

        if (is_array($value)) {
            $size = count($value);
            if ($size > 0) {
                return $column . ' ' . $operator .' ' .  $this->asEnclosedCsv(array_fill(0, $size, $marker));
            }
            return '1 = 0';
        }

        if ($value instanceof SelectBuilder) {
            return $column . ' ' . $operator . ' ' . $this->formatSubQuery($value);
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
        $marker = $this->getParameterMarker();
        return $column . ' ' . $operator . ' ' . $marker . ' AND ' . $marker;
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
            return $column . ' ' . $operator . ' ' . $this->formatSubQuery($value);
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
        return $column . ' ' . $operator . ' ' . $this->getParameterMarker();
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
        $marker = $this->getParameterMarker();

        if ($value instanceof Range) {
            $lowerOperator = $negated
                ? ($value->lowerClosed ? '<' : '<=')
                : ($value->lowerClosed ? '>=' : '>');
            $upperOperator = $negated
                ? ($value->upperClosed ? '>' : '>=')
                : ($value->upperClosed ? '<=' : '<');
            $expr = $column . ' ' . $lowerOperator . ' ' . $marker;
            $expr.= $negated ? ' OR ' : ' AND ';
            $expr.= $column . ' ' . $upperOperator . ' ' . $marker;
            return $expr;
        }

        throw new RuntimeException('Unknown condition');
    }

    /**
     * @param SelectBuilder $builder
     * @return string
     */
    protected function formatSubQuery(SelectBuilder $builder): string
    {
        return '('.$builder->prepare().')';
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
            $clause[] = $this->columnize($column);
        }
        return 'GROUP BY ' . $this->asCsv($clause);
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
            $clause[] = $this->columnize($column) . ' ' . $sort->value;
        }
        return 'ORDER BY ' . $this->asCsv($clause);
    }

    /**
     * @param ConditionsStatement $statement
     * @return string
     */
    protected function formatLimitPart(ConditionsStatement $statement): string
    {
        return $statement->limit !== null
            ? 'LIMIT ' . $statement->limit
            : '';
    }

    /**
     * @param SelectStatement $statement
     * @return string
     */
    protected function formatOffsetPart(SelectStatement $statement): string
    {
        return $statement->offset !== null
            ? 'OFFSET ' . $statement->offset
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

        $columns = array_map(
            fn(string $column): string => $this->quote($column),
            $statement->returning
        );

        return 'RETURNING ' . $this->asCsv($columns);
    }

    /**
     * @param string $column
     * @param string $path
     * @return string
     */
    public function formatJsonExtract(string $column, string $path): string
    {
        return $this->columnize($column) . '->' . '"' . $path . '"';
    }

    /**
     * @param string $name
     * @return string
     */
    public function tableize(string $name): string
    {
        $as = null;
        if (preg_match('/( as | AS )/', $name)) {
            $delim = preg_quote($this->getIdentifierDelimiter(), null);
            $tablePatternPart = $delim . '?(?<table>[^ ' . $delim . ']+)' . $delim . '?';
            $asPatternPart = '( (AS|as) ' . $delim . '?(?<as>[^' . $delim . ']+)' . $delim . '?)?';
            $pattern = '/^' . $tablePatternPart . $asPatternPart . '$/';
            $match = null;
            if (preg_match($pattern, $name, $match)) {
                $name = (string)$match['table'];
                $as = $match['as'] ?? null;
            }
        }
        $name = $this->quote($name);
        if ($as !== null) {
            $name .= ' AS ' . $this->quote($as);
        }
        return $name;
    }

    /**
     * @param string $name
     * @param bool $withAlias
     * @return string
     */
    public function columnize(string $name, bool $withAlias = false): string
    {
        $table = null;
        $as = null;
        if (preg_match('/(\.| as | AS )/', $name)) {
            $delim = preg_quote($this->getIdentifierDelimiter(), null);
            $patterns = [];
            $patterns[] = '(' . $delim . '?(?<table>[^\.'. $delim . ']+)' . $delim . '?\.)?';
            $patterns[] = $delim . '?(?<column>[^ ' . $delim . ']+)' . $delim . '?';
            if ($withAlias) {
                $patterns[] = '( (AS|as) ' . $delim . '?(?<as>[^'.$delim.']+)' . $delim . '?)?';
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
            $name = $this->quote($name);
        }

        if ($table !== null) {
            $name = $this->quote($table) . '.' . $name;
        }

        if ($as !== null) {
            $name.= ' AS ' . $this->quote($as);
        }

        return $name;
    }

    /**
     * @param string $str
     * @return string
     */
    public function quote(string $str): string
    {
        $delimiter = $this->getIdentifierDelimiter();
        return $delimiter . $this->escape($str, $delimiter) . $delimiter;
    }

    /**
     * @return string
     */
    public function getParameterMarker(): string
    {
        return '?';
    }

    /**
     * @return string
     */
    public function getIdentifierDelimiter(): string
    {
        return '"';
    }

    /**
     * @return string
     */
    public function getStringLiteralDelimiter(): string
    {
        return "'";
    }

    /**
     * @param ConditionDefinition $def
     * @return string
     */
    protected function getDefinedColumn(ConditionDefinition $def): string
    {
        $column = $def->column;

        if (is_string($column)) {
            return $this->columnize($column);
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
    protected function getBindingsForConditions(ConditionsStatement $statement): array
    {
        $bindings = [];
        if ($statement->where !== null) {
            foreach ($statement->where as $cond) {
                $this->addBindingsForCondition($bindings, $cond);
            }
        }
        return $bindings;
    }

    /**
     * @param array<int, mixed> $bindings
     * @param ConditionDefinition $def
     * @return void
     */
    protected function addBindingsForCondition(array &$bindings, ConditionDefinition $def): void
    {
        while ($def !== null) {
            $value = $def->value;
            if (is_iterable($value)) {
                foreach ($value as $binding) {
                    $bindings[] = $binding;
                }
            }
            elseif ($value instanceof Expr || $value instanceof SelectBuilder) {
                foreach ($value->getBindings() as $binding) {
                    $bindings[] = $binding;
                }
            }
            else {
                $bindings[] = $value;
            }
            $def = $def->next;
        }
    }

    /**
     * @param array<mixed> $bindings
     * @return array<mixed>
     */
    protected function parameterizeBindings(array $bindings): array
    {
        $parameters = [];
        foreach($bindings as $name => $binding) {
            $parameters[$name] = $this->parameterize($binding);
        }
        return $parameters;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    protected function parameterize(mixed $value): mixed
    {
        if (is_iterable($value)) {
            /** @var iterable<array-key, mixed> $value */
            return Json::encode(Arr::from($value));
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format($this->getDateTimeFormat());
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        return $value;
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
     * @param string $str
     * @param string $escaping
     * @return string
     */
    protected function escape(string $str, string $escaping): string
    {
        return str_replace($escaping, $escaping . $escaping, $str);
    }

    /**
     * @param string $str
     * @return string
     */
    protected function literalize(string $str): string
    {
        $delimiter = $this->getStringLiteralDelimiter();
        return $delimiter . $this->escape($str, $delimiter) . $delimiter;
    }

    /**
     * @return string
     */
    protected function getDateTimeFormat(): string
    {
        return DateTimeInterface::RFC3339_EXTENDED;
    }
}
