<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements\Index;

use Kirameki\Core\Exceptions\UnreachableException;
use Kirameki\Database\Query\Statements\SortOrder;
use Kirameki\Database\Schema\SchemaHandler;
use Kirameki\Database\Schema\Statements\SchemaBuilder;
use function is_int;
use function is_string;
use function iterator_to_array;

/**
 * @extends SchemaBuilder<CreateIndexStatement>
 */
class CreateIndexBuilder extends SchemaBuilder
{
    /**
     * @param SchemaHandler $handler
     * @param IndexType $type
     * @param string $table
     * @param iterable<string, SortOrder>|iterable<int, string> $columns
     */
    public function __construct(
        SchemaHandler $handler,
        IndexType $type,
        string $table,
        iterable $columns,
    )
    {
        parent::__construct($handler, new CreateIndexStatement($type, $table, $this->normalizeColumns($columns)));
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
     * @param iterable<int, string>|iterable<string, SortOrder> $columns
     * @return $this
     */
    public function columns(iterable $columns): static
    {
        $this->statement->columns = $this->normalizeColumns($columns);
        return $this;
    }

    /**
     * @param iterable<int, string>|iterable<string, SortOrder> $columns
     * @return array<string, SortOrder>
     */
    protected function normalizeColumns(iterable $columns): array
    {
        $normalized = [];
        foreach ($columns as $column => $order) {
            if (is_string($column) && $order instanceof SortOrder) {
                $normalized[$column] = $order;
            } elseif (is_int($column) && is_string($order)) {
                $normalized[$order] = SortOrder::Ascending;
            } else {
                throw new UnreachableException('Invalid index column definition format.', [
                    'statement' => $this->statement,
                    'columns' => $columns,
                ]);
            }
        }
        return $normalized;
    }
}
