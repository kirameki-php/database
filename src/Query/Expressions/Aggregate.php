<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Expressions;

use Kirameki\Database\Expression;

abstract class Aggregate extends QueryFunction
{
    /**
     * @param string $column
     * @param string|null $as
     */
    public function __construct(
        string|Expression $column = '*',
        ?string $as = null,
    )
    {
        parent::__construct($column, $as);
    }
}
