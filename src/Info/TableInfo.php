<?php declare(strict_types=1);

namespace Kirameki\Database\Info;

use Kirameki\Collections\Map;
use Kirameki\Collections\Vec;
use Kirameki\Database\Connection;
use Kirameki\Database\Info\Statements\ListColumnsStatement;
use Kirameki\Database\Info\Statements\ListForeignKeysStatement;
use Kirameki\Database\Info\Statements\ListIndexesStatement;
use stdClass;

class TableInfo
{
    /**
     * @var Map<string, ColumnInfo>
     */
    public Map $columns {
        get => $this->columns ??= $this->resolveColumns();
    }

    /**
     * @var Vec<IndexInfo>
     */
    public Vec $indexes {
        get => $this->indexes ??= $this->resolveIndexes();
    }

    /**
     * @var Vec<ForeignKeyInfo>
     */
    public Vec $foreignKeys {
        get => $this->foreignKeys ??= $this->resolveForeignKeys();
    }

    /**
     * @param Connection $connection
     * @param string $table
     */
    public function __construct(
        protected readonly Connection $connection,
        public readonly string $table,
    )
    {
    }

    /**
     * @return Map<string, ColumnInfo>
     */
    protected function resolveColumns(): Map
    {
        return $this->connection->query()->execute(new ListColumnsStatement($this->table))
            ->map(static fn(stdClass $r) => new ColumnInfo($r->name, $r->type, $r->nullable, $r->position))
            ->keyBy(static fn(ColumnInfo $c) => $c->name);
    }

    /**
     * @return Vec<IndexInfo>
     */
    protected  function resolveIndexes(): Vec
    {
        return $this->connection->query()->execute(new ListIndexesStatement($this->table))
            ->map(static fn(stdClass $r) => new IndexInfo($r->name, $r->columns, $r->type));
    }

    /**
     * @return Vec<ForeignKeyInfo>
     */
    protected function resolveForeignKeys(): Vec
    {
        return $this->connection->query()->execute(new ListForeignKeysStatement($this->table))
            ->map(static fn(stdClass $r) => new ForeignKeyInfo($r->name, $r->columns, $r->referencedTable, $r->referencedColumns));
    }
}
