<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Expressions;

use Kirameki\Database\Schema\Formatters\Formatter;

class Raw extends Expr
{
    /**
     * @var string
     */
    public readonly string $value;

    /**
     * @param string $value
     */
    public function __construct(string $value)
    {
        $this->value = $value;
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
