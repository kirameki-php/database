<?php declare(strict_types=1);

namespace Kirameki\Database\Statements;

use BackedEnum;
use DateTimeInterface;
use Kirameki\Core\Exceptions\UnreachableException;
use Kirameki\Core\Json;
use function count;
use function current;
use function is_bool;
use function is_iterable;
use function is_string;
use function iterator_to_array;
use function next;
use function preg_replace_callback;
use function str_replace;

abstract class StatementFormatter
{
    public function __construct(
        protected readonly string $identifierDelimiter,
        protected readonly string $literalDelimiter,
        protected readonly string $dateTimeFormat,
    )
    {
    }

    /**
     * @param string $str
     * @return string
     */
    public function asIdentifier(string $str): string
    {
        $delimiter = $this->identifierDelimiter;
        return $delimiter . $this->escape($str, $delimiter) . $delimiter;
    }

    /**
     * @param string $str
     * @return string
     */
    protected function asLiteral(string $str): string
    {
        $delimiter = $this->literalDelimiter;
        return $delimiter . $this->escape($str, $delimiter) . $delimiter;
    }

    /**
     * FOR DEBUGGING ONLY
     *
     * @param Statement $statement
     * @return string
     */
    public function interpolate(Statement $statement): string
    {
        $parameters = $this->stringifyParameters($statement->getParameters());
        $remains = count($parameters);

        return (string) preg_replace_callback('/\?\??/', function ($matches) use (&$parameters, &$remains) {
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
     * @param iterable<array-key, mixed> $parameters
     * @return array<mixed>
     */
    protected function stringifyParameters(iterable $parameters): array
    {
        $strings = [];
        foreach($parameters as $name => $parameter) {
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
     * @param string $str
     * @param string $escaping
     * @return string
     */
    protected function escape(string $str, string $escaping): string
    {
        return str_replace($escaping, $escaping . $escaping, $str);
    }
}
