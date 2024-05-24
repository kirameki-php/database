<?php declare(strict_types=1);

namespace Kirameki\Database\Migration;

use Closure;
use Kirameki\Database\Config\MigrationConfig;
use Kirameki\Database\Connection;
use Kirameki\Database\DatabaseManager;
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
     * @template T
     * @param Closure(): T $callback
     * @return T
     */
    public function withDistributedLock(Closure $callback): mixed
    {
        $connection = $this->getConnection();
        return $connection->transaction(function() use ($connection, $callback) {
            $connection->query()->executeRaw("LOCK TABLES {$this->getTableName()} WRITE");
            $result = $callback();
            $connection->query()->executeRaw('UNLOCK TABLES');
            return $result;
        });
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
     * @param list<SchemaResult<covariant SchemaStatement>> $results
     * @return void
     */
    public function push(string $name, array $results): void
    {
        $commands = [];
        foreach ($results as $result) {
            foreach ($result->commands as $command) {
                $commands[] = $command;
            }
        }

        $this->getConnection()->query()
            ->insertInto($this->getTableName())
            ->value(['name' => $name, 'commands' => $commands])
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
            ->execute()
            ->ensureAffectedRowIs(1);
    }

    /**
     * @return void
     */
    public function createRepository(): void
    {
        $builder = $this->getConnection()->schema()->createTable($this->getTableName());
        $builder->int('id')->autoIncrement()->primaryKey();
        $builder->string('name');
        $builder->json('commands');
        $builder->timestamps();
        $builder->index(['name'])->unique();
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
}
