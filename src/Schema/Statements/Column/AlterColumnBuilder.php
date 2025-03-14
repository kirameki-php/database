<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements\Column;

use Kirameki\Database\Schema\SchemaHandler;

class AlterColumnBuilder extends ColumnBuilder
{
    /**
     * @param SchemaHandler $handler
     * @param AlterColumnAction $action
     */
    public function __construct(
        SchemaHandler $handler,
        protected AlterColumnAction $action,
    )
    {
        parent::__construct($handler, $action->definition);
    }

    /**
     * @param int|null $size
     * @return ColumnBuilder
     */
    public function int(?int $size = null): ColumnBuilder
    {
        return $this->columnType(__FUNCTION__, $size);
    }

    /**
     * @return ColumnBuilder
     */
    public function float(): ColumnBuilder
    {
        return $this->columnType(__FUNCTION__);
    }

    /**
     * @param int|null $precision
     * @param int|null $scale
     * @return ColumnBuilder
     */
    public function decimal(?int $precision = null, ?int $scale = null): ColumnBuilder
    {
        return $this->columnType(__FUNCTION__, $precision, $scale);
    }

    /**
     * @return ColumnBuilder
     */
    public function bool(): ColumnBuilder
    {
        return $this->columnType(__FUNCTION__);
    }

    /**
     * @param int|null $precision
     * @return ColumnBuilder
     */
    public function datetime(?int $precision = null): ColumnBuilder
    {
        return $this->columnType(__FUNCTION__, $precision);
    }

    /**
     * @param int|null $size
     * @return ColumnBuilder
     */
    public function string(?int $size = null): ColumnBuilder
    {
        return $this->columnType(__FUNCTION__, $size);
    }

    /**
     * @return ColumnBuilder
     */
    public function text(): ColumnBuilder
    {
        return $this->columnType(__FUNCTION__);
    }

    /**
     * @return ColumnBuilder
     */
    public function json(): ColumnBuilder
    {
        return $this->columnType(__FUNCTION__);
    }

    /**
     * @return ColumnBuilder
     */
    public function binary(): ColumnBuilder
    {
        return $this->columnType(__FUNCTION__);
    }

    /**
     * @return ColumnBuilder
     */
    public function uuid(): ColumnBuilder
    {
        return $this->columnType(__FUNCTION__);
    }

    /**
     * @param string $type
     * @param int|null $size
     * @param int|null $scale
     * @return $this
     */
    protected function columnType(string $type, ?int $size = null, ?int $scale = null): static
    {
        $this->definition->type = $type;
        $this->definition->size = $size;
        $this->definition->scale = $scale;
        return $this;
    }

    /**
     * @return $this
     */
    public function first(): static
    {
        $this->action->positionType = 'FIRST';
        return $this;
    }

    /**
     * @param string $column
     * @return $this
     */
    public function after(string $column): static
    {
        $this->action->positionType = 'AFTER';
        $this->action->positionColumn = $column;
        return $this;
    }
}
