<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Expressions;

use Kirameki\Database\Query\Formatters\Formatter;

class Aggregate extends Column
{
    /**
     * @var string
     */
    public readonly string $function;

    /**
     * @var string|null
     */
    public readonly ?string $as;

    /**
     * @param string $function
     * @param string $column
     * @param string|null $as
     */
    public function __construct(string $function, string $column, ?string $as = null)
    {
        $this->function = $function;
        $this->as = $as;
        parent::__construct($column);
    }

    /**
     * @param Formatter $formatter
     * @return string
     */
    public function prepare(Formatter $formatter): string
    {
        $expr = $this->function;
        $expr.= '(';
        $expr.= parent::prepare($formatter);
        $expr.= ')';
        if ($this->as !== null) {
            $expr.= ' AS ' . $formatter->quote($this->as);
        }
        return $expr;
    }
}
