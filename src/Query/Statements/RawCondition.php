<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

class RawCondition extends Condition
{
    /**
     * @param string $value
     */
    public function __construct(string $value)
    {
        parent::__construct($value);
    }
}
