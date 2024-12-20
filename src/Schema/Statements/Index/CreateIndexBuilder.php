<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements\Index;

use Kirameki\Database\Query\Support\SortOrder;
use Kirameki\Database\Schema\SchemaHandler;
use Kirameki\Database\Schema\Statements\SchemaBuilder;

/**
 * @extends SchemaBuilder<CreateIndexStatement>
 */
class CreateIndexBuilder extends SchemaBuilder
{
    /**
     * @param SchemaHandler $handler
     * @param string $table
     */
    public function __construct(
        SchemaHandler $handler,
        string $table,
        bool $unique = false,
    )
    {
        parent::__construct($handler, new CreateIndexStatement($table));
        $this->statement->unique = $unique;
    }

    /**
     * @param string $column
     * @param string|null $order
     * @return $this
     */
    public function column(string $column, ?string $order = null): static
    {
        $this->statement->columns[$column] = $order ?? SortOrder::Ascending;
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
}
