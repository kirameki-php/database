<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Expressions;

use Kirameki\Database\Query\Formatters\Formatter;

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
    public function prepare(Formatter $formatter): string
    {
        return $this->value;
    }
}
