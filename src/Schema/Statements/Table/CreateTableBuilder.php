<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements\Table;

use Kirameki\Collections\Utils\Arr;
use Kirameki\Core\Exceptions\UnreachableException;
use Kirameki\Database\Query\Statements\SortOrder;
use Kirameki\Database\Schema\SchemaHandler;
use Kirameki\Database\Schema\Statements\Column\ColumnBuilder;
use Kirameki\Database\Schema\Statements\Column\ColumnDefinition;
use Kirameki\Database\Schema\Statements\Column\IntColumnBuilder;
use Kirameki\Database\Schema\Statements\Column\TimestampColumnBuilder;
use Kirameki\Database\Schema\Statements\Column\UuidColumnBuilder;
use Kirameki\Database\Schema\Statements\ForeignKey\ForeignKeyBuilder;
use Kirameki\Database\Schema\Statements\ForeignKey\ForeignKeyConstraint;
use Kirameki\Database\Schema\Statements\Index\CreateIndexBuilder;
use Kirameki\Database\Schema\Statements\Index\IndexType;
use Kirameki\Database\Schema\Statements\SchemaBuilder;
use function is_int;

/**
 * @extends SchemaBuilder<CreateTableStatement>
 */
class CreateTableBuilder extends SchemaBuilder
{
    /**
     * @param SchemaHandler $handler
     * @param string $table
     * @param bool $temporary
     */
    public function __construct(
        SchemaHandler $handler,
        string $table,
        bool $temporary = false,
    )
    {
        parent::__construct($handler, new CreateTableStatement($table, $temporary));
    }

    /**
     * @param string|null $column
     * @param int|null $startFrom
     * @return void
     */
    public function id(?string $column = null, ?int $startFrom = null): void
    {
        $this->int($column ?? 'id')->autoIncrement($startFrom)->primaryKey();
    }

    /**
     * @param string $column
     * @param int|null $size
     * @return IntColumnBuilder
     */
    public function int(string $column, ?int $size = null): IntColumnBuilder
    {
        return new IntColumnBuilder($this->handler, $this->addDefinition($column, __FUNCTION__, $size));
    }

    /**
     * @param string $column
     * @param int|null $size
     * @return ColumnBuilder
     */
    public function float(string $column, ?int $size = null): ColumnBuilder
    {
        return $this->column($column, __FUNCTION__, $size);
    }

    /**
     * @param string $column
     * @param int|null $precision
     * @param int|null $scale
     * @return ColumnBuilder
     */
    public function decimal(string $column, ?int $precision = null, ?int $scale = null): ColumnBuilder
    {
        return $this->column($column, __FUNCTION__, $precision, $scale);
    }

    /**
     * @param string $column
     * @return ColumnBuilder
     */
    public function bool(string $column): ColumnBuilder
    {
        return $this->column($column, __FUNCTION__);
    }

    /**
     * @param string $column
     * @param int|null $precision
     * @return TimestampColumnBuilder
     */
    public function timestamp(string $column, ?int $precision = null): ColumnBuilder
    {
        return new TimestampColumnBuilder($this->handler, $this->addDefinition($column, __FUNCTION__, $precision));
    }

    /**
     * @param string $column
     * @param int|null $size
     * @return ColumnBuilder
     */
    public function string(string $column, ?int $size = null): ColumnBuilder
    {
        return $this->column($column, __FUNCTION__, $size);
    }

    /**
     * @param string $column
     * @return ColumnBuilder
     */
    public function text(string $column): ColumnBuilder
    {
        return $this->column($column, __FUNCTION__);
    }

    /**
     * @param string $column
     * @return ColumnBuilder
     */
    public function json(string $column): ColumnBuilder
    {
        return $this->column($column, __FUNCTION__);
    }

    /**
     * @param string $column
     * @return UuidColumnBuilder
     */
    public function uuid(string $column): UuidColumnBuilder
    {
        return new UuidColumnBuilder($this->handler, $this->addDefinition($column, __FUNCTION__));
    }

    /**
     * @param iterable<int, string>|iterable<string, SortOrder> $columns
     * @return void
     */
    public function primaryKey(iterable $columns): void
    {
        $this->statement->primaryKey ??= new PrimaryKeyConstraint();
        foreach ($columns as $column => $order) {
            if (is_string($column) && $order instanceof SortOrder) {
                $this->statement->primaryKey->columns[$column] = $order;
            } elseif (is_int($column) && is_string($order)) {
                $this->statement->primaryKey->columns[$order] = SortOrder::Ascending;
            } else {
                throw new UnreachableException('Invalid primary key column definition format.', [
                    'statement' => $this->statement,
                    'columns' => $columns,
                ]);
            }
        }
    }

    /**
     * @param iterable<string, SortOrder>|iterable<int, string> $columns
     * @return CreateIndexBuilder
     */
    public function index(iterable $columns): CreateIndexBuilder
    {
        return $this->newIndexBuilder(IndexType::Default, $columns);
    }

    /**
     * @param iterable<string, SortOrder>|iterable<int, string> $columns
     * @return CreateIndexBuilder
     */
    public function uniqueIndex(iterable $columns): CreateIndexBuilder
    {
        return $this->newIndexBuilder(IndexType::Unique, $columns);
    }

    /**
     * @param iterable<int, string> $columns
     * @param string $referencedTable
     * @param iterable<int, string> $referencedColumns
     * @return ForeignKeyBuilder
     */
    public function foreignKey(iterable $columns, string $referencedTable, iterable $referencedColumns): ForeignKeyBuilder
    {
        $constraint = new ForeignKeyConstraint(
            Arr::values($columns),
            $referencedTable,
            Arr::values($referencedColumns),
        );
        $this->statement->foreignKeys[] = $constraint;
        return new ForeignKeyBuilder($constraint);
    }

    /**
     * @param string $name
     * @param string $type
     * @param int|null $size
     * @param int|null $scale
     * @return ColumnBuilder
     */
    protected function column(string $name, string $type, ?int $size = null, ?int $scale = null): ColumnBuilder
    {
        return new ColumnBuilder($this->handler, $this->addDefinition($name, $type, $size, $scale));
    }

    /**
     * @param IndexType $type
     * @param iterable<string, SortOrder>|iterable<int, string> $columns
     * @return CreateIndexBuilder
     */
    protected function newIndexBuilder(IndexType $type, iterable $columns): CreateIndexBuilder
    {
        $builder = new CreateIndexBuilder($this->handler, $type, $this->statement->table, $columns);
        $this->statement->indexes[] = $builder->statement;
        return $builder;
    }

    /**
     * @param string $name
     * @param string $type
     * @param int|null $size
     * @param int|null $scale
     * @return ColumnDefinition
     */
    protected function addDefinition(string $name, string $type, ?int $size = null, ?int $scale = null): ColumnDefinition
    {
        $definition = new ColumnDefinition($name, $type, $size, $scale, false);
        $this->statement->columns[] = $definition;
        return $definition;
    }
}
