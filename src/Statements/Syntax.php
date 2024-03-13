<?php declare(strict_types=1);

namespace Kirameki\Database\Statements;

use function str_replace;

abstract class Syntax
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
     * @param string $str
     * @param string $escaping
     * @return string
     */
    protected function escape(string $str, string $escaping): string
    {
        return str_replace($escaping, $escaping . $escaping, $str);
    }
}
