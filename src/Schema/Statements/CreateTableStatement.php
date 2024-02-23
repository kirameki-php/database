<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

class CreateTableStatement extends BaseStatement
{
    /**
     * @var ColumnDefinition[]
     */
    public array $columns;

    /**
     * @var PrimaryKeyConstraint|null
     */
    public ?PrimaryKeyConstraint $primaryKey;

    /**
     * @var CreateIndexStatement[]
     */
    public array $indexes;

    /**
     * @param string $table
     */
    public function __construct(string $table)
    {
        parent::__construct($table);
        $this->columns = [];
        $this->primaryKey = null;
        $this->indexes = [];
    }
}