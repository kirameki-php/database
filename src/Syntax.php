<?php declare(strict_types=1);

namespace Kirameki\Database;

use Kirameki\Collections\Utils\Arr;
use Kirameki\Database\Adapters\DatabaseConfig;
use Kirameki\Database\Query\Statements\Executable;
use Kirameki\Database\Query\Statements\QueryStatement;
use Kirameki\Database\Query\Support\QueryTags;
use Kirameki\Database\Query\Support\TagsFormat;
use function implode;
use function rawurlencode;
use function str_replace;

abstract class Syntax
{
    /**
     * @param DatabaseConfig $config
     * @param string $identifierDelimiter
     * @param string $literalDelimiter
     */
    public function __construct(
        protected readonly DatabaseConfig $config,
        protected readonly string $identifierDelimiter = '"',
        protected readonly string $literalDelimiter = "'",
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
}
