<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema\Expressions;

use Kirameki\Database\Statements\Schema\Formatters\Formatter;

class Raw extends Expr
{
    /**
     * @param string $value
     */
    public function __construct(
        public readonly string $value,
    )
    {
    }

    /**
     * @param Formatter $formatter
     * @return string
     */
    public function toSql(Formatter $formatter): string
    {
        return $this->value;
    }
}
