<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema;

use Kirameki\Collections\Utils\Arr;
use Kirameki\Database\Connection;
use RuntimeException;

/**
 * @extends SchemaBuilder<CreateTableStatement>
 */
class CreateTableBuilder extends SchemaBuilder
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
        parent::__construct($connection, new CreateTableStatement($syntax, $table));
    }

    /**
     * @param string $column
     * @param int|null $size
     * @return ColumnBuilder
     */
    public function int(string $column, ?int $size = null): ColumnBuilder
    {
        return $this->column($column, __FUNCTION__, $size);
    }

    /**
     * @param string $column
     * @return ColumnBuilder
     */
    public function float(string $column): ColumnBuilder
    {
        return $this->column($column, __FUNCTION__);
    }

    /**
     * @param string $column
     * @return ColumnBuilder
     */
    public function double(string $column): ColumnBuilder
    {
        return $this->column($column, __FUNCTION__);
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
     * @return ColumnBuilder
     */
    public function date(string $column): ColumnBuilder
    {
        return $this->column($column, __FUNCTION__);
    }

    /**
     * @param string $column
     * @param int|null $precision
     * @return ColumnBuilder
     */
    public function datetime(string $column, ?int $precision = null): ColumnBuilder
    {
        return $this->column($column, __FUNCTION__, $precision);
    }

    /**
     * @param string $column
     * @return ColumnBuilder
     */
    public function time(string $column): ColumnBuilder
    {
        return $this->column($column, __FUNCTION__);
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
     * @return ColumnBuilder
     */
    public function binary(string $column): ColumnBuilder
    {
        return $this->column($column, __FUNCTION__);
    }

    /**
     * @param string $column
     * @return ColumnBuilder
     */
    public function uuid(string $column): ColumnBuilder
    {
        return $this->column($column, __FUNCTION__);
    }

    /**
     * @param int|null $precision
     * @return ColumnBuilderAggregate
     */
    public function timestamps(?int $precision = null): ColumnBuilderAggregate
    {
        return new ColumnBuilderAggregate([
            $this->datetime('createdAt', $precision)->currentAsDefault(),
            $this->datetime('updatedAt', $precision)->currentAsDefault(),
        ]);
    }

    /**
     * @param iterable<array-key, string> $columns
     * @return void
     */
    public function primaryKey(iterable $columns): void
    {
        $this->statement->primaryKey ??= new PrimaryKeyConstraint();
        foreach ($columns as $column => $order) {
            is_string($column)
                ? $this->statement->primaryKey->columns[$column] = $order
                : $this->statement->primaryKey->columns[$order] = 'ASC';
        }
    }

    /**
     * @param iterable<array-key, string> $columns
     * @return CreateIndexBuilder
     */
    public function index(iterable $columns): CreateIndexBuilder
    {
        $builder = new CreateIndexBuilder($this->connection, $this->statement->table);
        $this->statement->indexes[] = $builder->statement;
        return $builder->columns($columns);
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
        $definition = new ColumnDefinition($name, $type, $size, $scale);
        $this->statement->columns[] = $definition;
        return new ColumnBuilder($definition);
    }
}
