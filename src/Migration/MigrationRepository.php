<?php declare(strict_types=1);

namespace Kirameki\Database\Migration;

use Closure;
use Kirameki\Database\Config\MigrationConfig;
use Kirameki\Database\Connection;
use Kirameki\Database\DatabaseManager;
use Kirameki\Database\Query\Support\LockOption;
use Kirameki\Database\Schema\Statements\SchemaResult;
use Kirameki\Database\Schema\Statements\SchemaStatement;

readonly class MigrationRepository
{
    /**
     * @param DatabaseManager $db
     * @param MigrationConfig $config
     */
    public function __construct(
        protected DatabaseManager $db,
        protected MigrationConfig $config,
    )
    {
    }

    /**
     * @return Connection
     */
    protected function getConnection(): Connection
    {
        return $this->db->use($this->config->connection ?? $this->db->getDefaultConnectionName());
    }

    /**
     * @return string
     */
    protected function getTableName(): string
    {
        return $this->config->table;
    }

    /**
     * @return void
     */
    public function createRepository(): void
    {
        $builder = $this->getConnection()->schema()->createTable($this->getTableName());
        $builder->int('id')->autoIncrement()->primaryKey();
        $builder->string('name');
        $builder->datetime('createdAt')->currentAsDefault();
        $builder->uniqueIndex('name');
        $builder->execute();
    }

    /**
     * @return bool
     */
    public function RepositoryExists(): bool
    {
        return $this->getConnection()->info()->tableExists($this->getTableName());
    }

    /**
     * @return void
     */
    public function dropRepository(): void
    {
        $this->getConnection()->schema()->dropTable($this->getTableName())->execute();
    }

    /**
     * @template T
     * @param Closure(): T $callback
     * @return T
     */
    public function withDistributedLock(Closure $callback): mixed
    {
        // TODO Lock migration table
        return $callback();
    }

    /**
     * @return string|null
     */
    public function getLatestName(): ?string
    {
        return $this->getConnection()->query()
            ->select('name')
            ->from($this->getTableName())
            ->orderByDesc('id')
            ->firstOrNull()
            ?->name;
    }

    /**
     * @param string $name
     * @return void
     */
    public function push(string $name): void
    {
        $this->getConnection()->query()
            ->insertInto($this->getTableName())
            ->value(['name' => $name])
            ->execute();
    }

    /**
     * @param string $name
     * @return void
     */
    public function pop(string $name): void
    {
        $this->getConnection()->query()
            ->deleteFrom($this->getTableName())
            ->where('name', $name)
            ->execute();
    }
}
