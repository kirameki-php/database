<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements\Table;

use Kirameki\Collections\Utils\Arr;
use Kirameki\Exceptions\LogicException;
use Kirameki\Database\Schema\Statements\Column\ColumnDefinition;
use Kirameki\Database\Schema\Statements\ForeignKey\ForeignKeyConstraint;
use Kirameki\Database\Schema\Statements\Index\CreateIndexStatement;
use Kirameki\Database\Schema\Statements\SchemaStatement;
use Kirameki\Database\Schema\Syntax\SchemaSyntax;
use Override;

class CreateTableStatement extends SchemaStatement
{
    /**
     * @param string $table
     * @param bool $temporary
     * @param list<ColumnDefinition> $columns
     * @param PrimaryKeyConstraint|null $primaryKey
     * @param list<CreateIndexStatement> $indexes
     * @param list<ForeignKeyConstraint> $foreignKeys
     */
    public function __construct(
        public readonly string $table,
        public readonly bool $temporary = false,
        public array $columns = [],
        public ?PrimaryKeyConstraint $primaryKey = null,
        public array $indexes = [],
        public array $foreignKeys = [],
    )
    {
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function toExecutable(SchemaSyntax $syntax): array
    {
        $this->preprocess();
        return $syntax->compileCreateTable($this);
    }

    /**
     * @return void
     */
    public function preprocess(): void
    {
        if (empty($this->columns)) {
            throw new LogicException('Table requires at least one column to be defined.', [
                'statement' => $this,
            ]);
        }
    }
}
