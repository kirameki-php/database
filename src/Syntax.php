<?php declare(strict_types=1);

namespace Kirameki\Database;

use Kirameki\Database\Adapters\DatabaseConfig;
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
