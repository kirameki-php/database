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
        $syntax = $connection->getAdapter()->getSchemaSyntax();
        parent::__construct($connection, new CreateIndexStatement($syntax, $table));
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
}
