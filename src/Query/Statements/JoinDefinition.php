<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Support\JoinType;

class JoinDefinition
{
    /**
     * @var JoinType
     */
    public JoinType $type;

    /**
     * @var string
     */
    public string $table;

    /**
     * @var ConditionDefinition
     */
    public ConditionDefinition $condition;

    /**
     * @param JoinType $type
     * @param string $table
     */
    public function __construct(JoinType $type, string $table)
    {
        $this->type = $type;
        $this->table = $table;
    }
}
