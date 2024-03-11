<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema;

class CreateTableStatement extends Statement
{
    /**
     * @var ColumnDefinition[]
     */
    public array $columns = [];

    /**
     * @var PrimaryKeyConstraint|null
     */
    public ?PrimaryKeyConstraint $primaryKey = null;

    /**
     * @var CreateIndexStatement[]
     */
    public array $indexes = [];
}