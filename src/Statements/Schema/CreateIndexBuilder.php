<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema;

use Kirameki\Database\Connection;
use RuntimeException;

/**
 * @extends SchemaBuilder<CreateIndexStatement>
 */
class CreateIndexBuilder extends SchemaBuilder
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
        parent::__construct($connection, new CreateIndexStatement($table));
    }

    /**
     * @param string $column
     * @param string|null $order
     * @return $this
     */
    public function column(string $column, ?string $order = null): static
    {
        $this->statement->columns[$column] = $order ?? 'ASC';
        return $this;
    }

    /**
     * @param iterable<array-key, string> $columns
     * @return $this
     */
    public function columns(iterable $columns): static
    {
        foreach ($columns as $column => $order) {
            is_string($column)
                ? $this->column($column, $order)
                : $this->column($order);
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function unique(): static
    {
        $this->statement->unique = true;
        return $this;
    }

    /**
     * @param string $comment
     * @return $this
     */
    public function comment(string $comment): static
    {
        $this->statement->comment = $comment;
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
            $syntax->formatCreateIndexStatement($this->statement),
        ];
    }

    /**
     * @return void
     */
    public function preprocess(): void
    {
        $columns = $this->statement->columns;

        if(empty($columns)) {
            throw new RuntimeException('At least 1 column needs to be defined to create an index.');
        }
    }
}
