<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

use Kirameki\Database\Schema\SchemaHandler;
use function iterator_to_array;

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
     * @param string $column
     * @param int $size
     * @return ColumnBuilder
     */
    public function int(string $column, int $size = ColumnDefinition::DEFAULT_INT_SIZE): ColumnBuilder
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
     * @param int $precision
     * @return TimestampColumnBuilder
     */
    public function datetime(string $column, int $precision = ColumnDefinition::DEFAULT_TIME_PRECISION): ColumnBuilder
    {
        return new TimestampColumnBuilder($this->addDefinition($column, __FUNCTION__, $precision));
    }

    /**
     * @param string $column
     * @param int $size
     * @return ColumnBuilder
     */
    public function string(string $column, int $size = ColumnDefinition::DEFAULT_STRING_SIZE): ColumnBuilder
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
     * @param int $precision
     * @return ColumnBuilderAggregate
     */
    public function timestamps(int $precision = ColumnDefinition::DEFAULT_TIME_PRECISION): ColumnBuilderAggregate
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
        $builder = new CreateIndexBuilder($this->handler, $this->statement->table);
        $this->statement->indexes[] = $builder->statement;
        return $builder->columns($columns);
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
            iterator_to_array($columns),
            $referencedTable,
            iterator_to_array($referencedColumns),
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
        return new ColumnBuilder($this->addDefinition($name, $type, $size, $scale));
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
