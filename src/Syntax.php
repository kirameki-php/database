<?php declare(strict_types=1);

namespace Kirameki\Database;

use BackedEnum;
use DateTimeInterface;
use Kirameki\Collections\Utils\Arr;
use Kirameki\Core\Exceptions\NotSupportedException;
use Kirameki\Core\Value;
use Kirameki\Database\Config\ConnectionConfig;
use Kirameki\Database\Config\DatabaseConfig;
use function array_filter;
use function array_map;
use function implode;
use function is_iterable;
use function is_string;
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
     * @param string $literalDelimiter
     * @param string $dateTimeFormat
     */
    public function __construct(
        protected readonly DatabaseConfig $databaseConfig,
        protected readonly ConnectionConfig $connectionConfig,
        protected readonly string $identifierDelimiter,
        protected readonly string $literalDelimiter,
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
        return $delimiter . $this->escape($string, $delimiter) . $delimiter;
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
        $delimiter = $this->literalDelimiter;
        return $delimiter . $this->escape($string, $delimiter) . $delimiter;
    }

    /**
     * @param string $string
     * @param string $escaping
     * @return string
     */
    protected function escape(string $string, string $escaping): string
    {
        return str_replace($escaping, '\\' . $escaping, $string);
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
     * @param array<scalar> $values
     * @return string
     */
    protected function asEnclosedCsv(array $values): string
    {
        return '(' . $this->asCsv($values) . ')';
    }

    /**
     * @param iterable<int, mixed> $columns
     * @return list<string>
     */
    public function asColumns(iterable $columns): array
    {
        return array_map($this->asColumn(...), Arr::values($columns));
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
    public function stringifyParameters(iterable $values): array
    {
        return array_map($this->stringifyParameter(...), Arr::values($values));
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function stringifyParameter(mixed $value): mixed
    {
        if (is_iterable($value)) {
            return $this->stringifyParameters($value);
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
