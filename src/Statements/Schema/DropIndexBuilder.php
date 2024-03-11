<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema;

use Kirameki\Database\Connection;
use RuntimeException;

/**
 * @extends SchemaBuilder<DropIndexStatement>
 */
class DropIndexBuilder extends SchemaBuilder
{
    /**
     * @param Connection $connection
     * @param string $table
     */
    public function __construct(
        Connection $connection,
        public readonly string $table,
    )
    {
        parent::__construct($connection, new DropIndexStatement($table));
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

    /**
     * @return string[]
     */
    public function build(): array
    {
        $this->preprocess();
        $syntax = $this->connection->getSchemaSyntax();
        return [
            $syntax->formatDropIndexStatement($this->statement)
        ];
    }

    /**
     * @return void
     */
    public function preprocess(): void
    {
        $name = $this->statement->name;
        $columns = $this->statement->columns;

        if($name === null && empty($columns)) {
            throw new RuntimeException('Name or column(s) are required to drop an index.');
        }
    }
}
