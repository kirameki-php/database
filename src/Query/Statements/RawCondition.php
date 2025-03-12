<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Raw;

class RawCondition extends Condition
{
    /**
     * @param string $value
     */
    public function __construct(string $value)
    {
        parent::__construct(new Raw($value));
    }
}
