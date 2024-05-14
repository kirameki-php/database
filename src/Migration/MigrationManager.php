<?php declare(strict_types=1);

namespace Kirameki\Database\Migration;

use Closure;
use DateTimeInterface;
use Kirameki\Collections\Utils\Arr;
use Kirameki\Collections\Vec;
use Kirameki\Database\DatabaseManager;
use Kirameki\Database\Schema\Statements\SchemaResult;
use Kirameki\Database\Schema\Statements\SchemaStatement;

readonly class MigrationManager
{
    /**
     * @param DatabaseManager $db
     * @param MigrationFinder $finder
     */
    public function __construct(
        protected DatabaseManager $db,
        protected MigrationFinder $finder,
    )
    {
    }

    /**
     * @param DateTimeInterface|null $since
     */
    public function up(?DateTimeInterface $since = null): void
    {
        foreach ($this->finder->scan($since) as $migration) {
            $this->withTransaction($migration, $migration->runUp(...));
        }
    }

    /**
     * @param DateTimeInterface|null $since
     */
    public function down(?DateTimeInterface $since = null): void
    {
        foreach ($this->finder->scan($since) as $migration) {
            $this->withTransaction($migration, $migration->runDown(...));
        }
    }

    /**
     * @param DateTimeInterface|null $since
     * @return Vec<SchemaResult<covariant SchemaStatement>>
     */
    public function inspectUp(?DateTimeInterface $since = null): Vec
    {
        $results = [];
        foreach ($this->finder->scan($since, true) as $migration) {
            $results[] = $migration->runUp();
        }
        return new Vec(Arr::flatten($results));
    }

    /**
     * @param DateTimeInterface|null $since
     * @return Vec<SchemaResult<covariant SchemaStatement>>
     */
    public function inspectDown(?DateTimeInterface $since = null): Vec
    {
        $results = [];
        foreach ($this->finder->scan($since, true) as $migration) {
            $results[] = $migration->runDown();
        }
        return new Vec(Arr::flatten($results));
    }

    /**
     * @param Migration $migration
     * @param Closure(): mixed $callback
     * @return void
     */
    protected function withTransaction(Migration $migration, Closure $callback): void
    {
        $connection = $migration->getConnection();
        $connection->adapter->getSchemaSyntax()->supportsDdlTransaction()
            ? $connection->transaction($callback)
            : $callback();
    }
}
