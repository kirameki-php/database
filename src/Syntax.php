<?php declare(strict_types=1);

namespace Kirameki\Database;

use BackedEnum;
use DateTimeInterface;
use Kirameki\Core\Json;
use Kirameki\Database\Config\ConnectionConfig;
use Kirameki\Database\Config\DatabaseConfig;
use function implode;
use function is_iterable;
use function iterator_to_array;
use function str_replace;

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
}
