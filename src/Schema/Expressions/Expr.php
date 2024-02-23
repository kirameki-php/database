<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Expressions;

use Kirameki\Database\Schema\Formatters\Formatter;

abstract class Expr
{
    /**
     * @param string $value
     * @return Raw
     */
    public static function raw(string $value): Raw
    {
        return new Raw($value);
    }

    /**
     * @param Formatter $formatter
     * @return string
     */
    abstract public function toSql(Formatter $formatter): string;
}
