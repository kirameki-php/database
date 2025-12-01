<?php declare(strict_types=1);

namespace Kirameki\Database;

use BackedEnum;
use Closure;
use DateTimeInterface;
use Kirameki\Collections\Utils\Arr;
use Kirameki\Exceptions\InvalidArgumentException;
use Kirameki\Database\Config\ConnectionConfig;
use Kirameki\Database\Config\DatabaseConfig;
use Kirameki\Database\Query\Statements\Tuple;
use Stringable;
use function array_filter;
use function array_map;
use function implode;
use function is_iterable;
use function preg_match;
use function preg_quote;
use function str_replace;
use function trim;

abstract class Syntax
{
    /**
     * @param DatabaseConfig $databaseConfig
     * @param ConnectionConfig $connectionConfig
     * @param string $identifierDelimiter
     * @param Closure(string): string $quoteLiteralFn
     * @param string $dateTimeFormat
     */
    public function __construct(
        protected readonly DatabaseConfig $databaseConfig,
        protected readonly ConnectionConfig $connectionConfig,
        protected readonly string $identifierDelimiter,
        protected readonly Closure $quoteLiteralFn,
        protected readonly string $dateTimeFormat,
    )
    {
    }

    /**
     * @param string $string
     * @return string
     */
    public function asIdentifier(string $string): string
    {
        $delimiter = $this->identifierDelimiter;
        $escaped = str_replace($delimiter, '\\' . $delimiter, $string);
        return $delimiter . $escaped . $delimiter;
    }

    /**
     * @param iterable<int, string> $strings
     * @return list<string>
     */
    public function asIdentifiers(iterable $strings): array
    {
        return array_map($this->asIdentifier(...), Arr::values($strings));
    }

    /**
     * @param string $string
     * @return string
     */
    public function asLiteral(string $string): string
    {
        return ($this->quoteLiteralFn)($string);
    }

    /**
     * @param array<scalar> $values
     * @return string
     */
    public function asCsv(array $values): string
    {
        return implode(', ', $values);
    }

    /**
     * @param array<scalar> $values
     * @return string
     */
    public function asEnclosedCsv(array $values): string
    {
        return '(' . $this->asCsv($values) . ')';
    }

    /**
     * @param iterable<int, string|Expression> $columns
     * @param bool $withAlias
     * @return list<string>
     */
    public function asColumns(iterable $columns, bool $withAlias = false): array
    {
        $results = [];
        foreach ($columns as $column) {
            $results[] = $this->asColumn($column, $withAlias);
        }
        return $results;
    }

    /**
     * @param string|Tuple|Expression $name
     * @param bool $withAlias
     * @return string
     */
    public function asColumn(string|Tuple|Expression $name, bool $withAlias = false): string
    {
        if (is_iterable($name)) {
            return $this->asEnclosedCsv($this->asColumns($name));
        }

        if ($name instanceof Expression) {
            return $name->toValue($this);
        }

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
     * @param string $comment
     * @return string
     */
    public function asSingleLineComment(string $comment): string
    {
        if (str_contains($comment, "\n")) {
            throw new InvalidArgumentException('Single line comment must not contain new line.', [
                'comment' => $comment,
            ]);
        }
        return "-- {$comment}";
    }

    /**
     * @param string $comment
     * @return string
     */
    public function asBlockComment(string $comment): string
    {
        return "/* {$comment} */";
    }

    /**
     * @param iterable<int, string|Expression> $values
     * @return list<string>
     */
    public function stringifyExpressions(iterable $values): array
    {
        $strings = [];
        foreach ($values as $value) {
            $strings[] = $this->stringifyExpression($value);
        }
        return $strings;
    }

    /**
     * @param string|Expression $expression
     * @return string
     */
    public function stringifyExpression(string|Expression $expression): string
    {
        if ($expression instanceof Expression) {
            return $expression->toValue($this);
        }
        return $expression;
    }

    /**
     * @param iterable<array-key, mixed> $values
     * @return list<mixed>
     */
    public function normalizeParameters(iterable $values): array
    {
        $normalized = [];
        foreach ($values as $value) {
            $normalized[] = $this->normalizeParameter($value);
        }
        return $normalized;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function normalizeParameter(mixed $value): mixed
    {
        if (is_iterable($value)) {
            return $this->normalizeParameters($value);
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format($this->dateTimeFormat);
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof Stringable) {
            return $value->__toString();
        }

        return $value;
    }

    /**
     * @param list<string|null> $parts
     * @return string
     */
    protected function concat(array $parts): string
    {
        return implode(' ', array_filter($parts, static fn($p): bool => $p !== null && trim($p) !== ''));
    }

    /**
     * @param list<string|Expression> $values
     * @return string
     */
    abstract public function formatCoalesce(array $values): string;

    /**
     * @param int|null $size
     * @return string
     */
    abstract public function formatCurrentTimestamp(?int $size = null): string;

    /**
     * @param string $target
     * @param string $path
     * @param string|null $as
     * @return string
     */
    abstract public function formatJsonExtract(string|Expression $target, string $path, ?string $as): string;

    /**
     * @return string
     */
    abstract public function formatUuid(): string;
}
