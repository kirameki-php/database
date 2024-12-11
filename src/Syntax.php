<?php declare(strict_types=1);

namespace Kirameki\Database;

use BackedEnum;
use DateTimeInterface;
use Kirameki\Collections\Utils\Arr;
use Kirameki\Database\Config\ConnectionConfig;
use Kirameki\Database\Config\DatabaseConfig;
use function array_filter;
use function array_map;
use function implode;
use function is_iterable;
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
        return str_replace($escaping, $escaping . $escaping, $string);
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
     * @param iterable<array-key, mixed> $values
     * @return list<mixed>
     */
    protected function stringifyParameters(iterable $values): array
    {
        return array_map($this->stringifyParameter(...), Arr::values($values));
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    protected function stringifyParameter(mixed $value): mixed
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
}
