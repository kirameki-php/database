<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema;

use Kirameki\Database\SchemaHandler;
use Kirameki\Database\Statements\Schema\Syntax\SchemaSyntax;

/**
 * @extends SchemaBuilder<DropIndexStatement>
 */
class DropIndexBuilder extends SchemaBuilder
{
    /**
     * @param SchemaHandler $handler
     * @param SchemaSyntax $syntax
     * @param string $table
     */
    public function __construct(
        SchemaHandler $handler,
        SchemaSyntax $syntax,
        public readonly string $table,
    )
    {
        parent::__construct($handler, new DropIndexStatement($syntax, $table));
    }

    /**
     * @param string $name
     * @return $this
     */
    public function name(string $name): static
    {
        $this->statement->name = $name;
        return $this;
    }

    /**
     * @param string $column
     * @return $this
     */
    public function column(string $column): static
    {
        $this->statement->columns[] = $column;
        return $this;
    }

    /**
     * @param iterable<int, string> $columns
     * @return $this
     */
    public function columns(iterable $columns): static
    {
        foreach ($columns as $column) {
            $this->column($column);
        }
        return $this;
    }
}
