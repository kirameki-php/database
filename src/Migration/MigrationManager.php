<?php declare(strict_types=1);

namespace Kirameki\Database\Migration;

use Closure;
use DateTimeInterface;
use Kirameki\Collections\Utils\Arr;
use Kirameki\Core\Exceptions\UnreachableException;
use Kirameki\Database\DatabaseManager;
use Kirameki\Event\EventManager;
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
     * @param EventManager $events
     * @param string $directory
     */
    public function __construct(
        protected DatabaseManager $db,
        protected EventManager $events,
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
            $this->withTransaction($migration, $migration->up(...));
        }
    }

    /**
     * @param DateTimeInterface|null $since
     */
    public function down(?DateTimeInterface $since = null): void
    {
        foreach ($this->readPendingMigrations($since, false) as $migration) {
            $this->withTransaction($migration, $migration->down(...));
        }
    }

    /**
     * @param DateTimeInterface|null $since
     * @return list<string>
     */
    public function inspectUp(?DateTimeInterface $since = null): array
    {
        $statements = [];
        foreach ($this->readPendingMigrations($since, true) as $migration) {
            $migration->up();
            $statements[] = $migration->getExecutedCommands();
        }
        return Arr::flatten($statements);
    }

    /**
     * @param DateTimeInterface|null $since
     * @return list<string>
     */
    public function inspectDown(?DateTimeInterface $since = null): array
    {
        // TODO implement
        return [];
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
                $migrations[] = new $className($this->db, $this->events, $dryRun);
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
     * @param Closure(): void $callback
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
