<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

use Kirameki\Collections\Utils\Arr;
use Kirameki\Database\Schema\Syntax\SchemaSyntax;
use RuntimeException;

class CreateTableStatement extends SchemaStatement
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

    public function __construct(
        SchemaSyntax $syntax,
        public readonly string $table,
    )
    {
        parent::__construct($syntax);
    }

    /**
     * @return list<string>
     */
    public function toCommands(): array
    {
        $this->preprocess();
        return $this->syntax->compileCreateTable($this);
    }

    /**
     * @return void
     */
    public function preprocess(): void
    {
        foreach ($this->columns as $column) {
            if ($column->type === 'int' && Arr::doesNotContain([null, 1, 2, 4, 8], $column->size)) {
                throw new RuntimeException('Size for integer must be 1, 2, 4, or 8 (bytes). ' . $column->size . ' given.');
            }
        }

        if (empty($this->columns)) {
            throw new RuntimeException('Table requires at least one column to be defined.');
        }
    }
}
