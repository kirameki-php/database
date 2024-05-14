<?php declare(strict_types=1);

namespace Kirameki\Database\Migration;

use Closure;
use DateTimeInterface;
use Kirameki\Collections\Utils\Arr;
use Kirameki\Collections\Vec;
use Kirameki\Database\DatabaseManager;
use Kirameki\Database\Schema\Statements\SchemaResult;
use Kirameki\Database\Schema\Statements\SchemaStatement;
use function assert;
use function basename;
use function glob;
use function is_a;
use function ltrim;
use function strstr;

readonly class MigrationManager
{
    /**
     * @param DatabaseManager $db
     * @param string $directory
     */
    public function __construct(
        protected DatabaseManager $db,
        protected string $directory,
    )
    {
    }

    /**
     * @param DateTimeInterface|null $since
     */
    public function up(?DateTimeInterface $since = null): void
    {
        foreach ($this->readPendingMigrations($since, false) as $migration) {
            $this->withTransaction($migration, $migration->runUp(...));
        }
    }

    /**
     * @param DateTimeInterface|null $since
     */
    public function down(?DateTimeInterface $since = null): void
    {
        foreach ($this->readPendingMigrations($since, false) as $migration) {
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
        foreach ($this->readPendingMigrations($since, true) as $migration) {
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
        foreach ($this->readPendingMigrations($since, true) as $migration) {
            $results[] = $migration->runDown();
        }
        return new Vec(Arr::flatten($results));
    }

    /**
     * @param DateTimeInterface|null $startAt
     * @param bool $dryRun
     * @return list<Migration>
     */
    protected function readPendingMigrations(?DateTimeInterface $startAt, bool $dryRun): array
    {
        $start = $startAt ? $startAt->format('YmdHis') : '00000000000000';
        $migrations = [];
        foreach ($this->getMigrationFiles() as $file) {
            $datetime = strstr(basename($file), '_', true);
            if ($datetime !== false && $datetime >= $start) {
                require_once $file;
                $className = $this->extractClassName($file);
                $migrations[] = new $className($this->db, $dryRun);
            }
        }
        return $migrations;
    }

    /**
     * @return list<string>
     */
    protected function getMigrationFiles(): array
    {
        return glob($this->directory . '/*.php') ?: [];
    }

    /**
     * @param string $file
     * @return class-string<Migration>
     */
    protected function extractClassName(string $file): string
    {
        $className = basename(ltrim((string) strstr($file, '_'), '_'), '.php');
        assert(is_a($className, Migration::class, true));
        return $className;
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
