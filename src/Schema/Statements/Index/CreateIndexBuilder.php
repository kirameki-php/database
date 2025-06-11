<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements\Index;

use Kirameki\Core\Exceptions\UnreachableException;
use Kirameki\Database\Query\Statements\SortOrder;
use Kirameki\Database\Schema\SchemaHandler;
use Kirameki\Database\Schema\Statements\SchemaBuilder;
use function is_string;

/**
 * @extends SchemaBuilder<CreateIndexStatement>
 */
class CreateIndexBuilder extends SchemaBuilder
{
    /**
     * @param SchemaHandler $handler
     * @param string $table
     * @param IndexType $type
     */
    public function __construct(
        SchemaHandler $handler,
        string $table,
        IndexType $type = IndexType::Undefined,
    )
    {
        parent::__construct($handler, new CreateIndexStatement($type, $table));
    }

    /**
     * @param string|null $name
     * @return $this
     */
    public function name(?string $name = null): static
    {
        $this->statement->name = $name;
        return $this;
    }

    /**
     * @param string $column
     * @param SortOrder|null $order
     * @return $this
     */
    public function column(string $column, ?SortOrder $order = null): static
    {
        $this->statement->columns[$column] = $order ?? SortOrder::Ascending;
        return $this;
    }

    /**
     * @param iterable<int, string>|iterable<string, SortOrder> $columns
     * @return $this
     */
    public function columns(iterable $columns): static
    {
        foreach ($columns as $column => $order) {
            if (is_string($column) && $order instanceof SortOrder) {
                $this->column($column, $order);
            } elseif (is_string($order)) {
                $this->column($order);
            } else {
                throw new UnreachableException('Invalid primary key column definition.');
            }
        }
        return $this;
    }
}
