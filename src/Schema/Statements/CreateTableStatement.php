<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

use Kirameki\Collections\Utils\Arr;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\Schema\Syntax\SchemaSyntax;
use Override;

class CreateTableStatement extends SchemaStatement
{
    /**
     * @param SchemaSyntax $syntax
     * @param string $table
     * @param bool $temporary
     * @param list<ColumnDefinition> $columns
     * @param PrimaryKeyConstraint|null $primaryKey
     * @param list<CreateIndexStatement> $indexes
     */
    public function __construct(
        SchemaSyntax $syntax,
        public readonly string $table,
        public readonly bool $temporary = false,
        public array $columns = [],
        public ?PrimaryKeyConstraint $primaryKey = null,
        public array $indexes = [],
    )
    {
        parent::__construct($syntax);
    }

    /**
     * @inheritDoc
     */
    #[Override]
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
                throw new LogicException('Size for integer must be 1, 2, 4, or 8 (bytes). ' . $column->size . ' given.', [
                    'statement' => $this,
                ]);
            }
        }

        if (empty($this->columns)) {
            throw new LogicException('Table requires at least one column to be defined.', [
                'statement' => $this,
            ]);
        }
    }
}
