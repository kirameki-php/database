<?php declare(strict_types=1);

namespace Kirameki\Database\Migration;

use Iterator;
use Kirameki\Collections\Utils\Arr;
use Kirameki\Collections\Vec;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\DatabaseManager;
use Kirameki\Database\Query\Support\SortOrder;
use Kirameki\Database\Schema\Statements\SchemaResult;
use Kirameki\Database\Schema\Statements\SchemaStatement;
use function strstr;
use const PHP_INT_MAX;

readonly class MigrationManager
{
    /**
     * @param DatabaseManager $db
     * @param MigrationRepository $repository
     * @param MigrationScanner $scanner
     */
    public function __construct(
        protected DatabaseManager $db,
        protected MigrationRepository $repository,
        protected MigrationScanner $scanner,
    )
    {
    }

    /**
     * @param int|null $version
     * @param int|null $steps
     * @return Vec<SchemaResult<covariant SchemaStatement>>
     */
    public function forward(?int $version = null, ?int $steps = null): Vec
    {
        $steps ??= PHP_INT_MAX;
        $version ??= 9999_99_99_99_99_99; // YmdHis

        return $this->repository->withDistributedLock(function() use ($version, $steps) {
            $resultsBundle = [];
            foreach ($this->getForwardMigrations($steps, $version) as $migration) {
                $resultsBundle[] = $migration->runForward();
                $this->repository->push($migration->getName());
            }
            return new Vec(Arr::flatten($resultsBundle));
        });
    }

    /**
     * @param int|null $version
     * @param int|null $steps
     * @return Vec<SchemaResult<covariant SchemaStatement>>
     */
    public function backward(?int $version = null, ?int $steps = null): Vec
    {
        $steps ??= $version !== null ? PHP_INT_MAX : 1;
        $version ??= 0;

        return $this->repository->withDistributedLock(function() use ($version, $steps) {
            $resultsBundle = [];
            foreach ($this->getBackwardMigrations($steps, $version) as $migration) {
                $resultsBundle[] = $migration->runBackward();
                $this->repository->pop($migration->getName());
            }
            return new Vec(Arr::flatten($resultsBundle));
        });
    }

    /**
     * @return Iterator<int, Migration>
     */
    protected function getForwardMigrations(int $steps, int $targetVersion): Iterator
    {
        $latestVersion = $this->getLatestVersion();
        foreach ($this->scanner->scan(SortOrder::Ascending) as $migration) {
            $migrationVersion = $this->getVersion($migration);
            if ($migrationVersion <= $latestVersion) {
                continue;
            }
            if ($steps <= 0 || $migrationVersion > $targetVersion) {
                break;
            }
            yield $migrationVersion => $migration;
            $steps -= 1;
        }
    }

    protected function getBackwardMigrations(int $steps, int $targetVersion): Iterator
    {
        $latestVersion = $this->getLatestVersion();
        foreach ($this->scanner->scan(SortOrder::Descending) as $migration) {
            $migrationVersion = $this->getVersion($migration);
            if ($migrationVersion > $latestVersion) {
                continue;
            }
            if ($steps <= 0 || $migrationVersion < $targetVersion) {
                break;
            }
            yield $migration;
            $steps -= 1;
        }
    }

    protected function getLatestVersion(): int
    {
        $latest = $this->repository->getLatestName();
        return $latest !== null
            ? $this->getVersionFromName($latest)
            : 0;
    }

    /**
     * @param Migration $migration
     * @return int
     */
    protected function getVersion(Migration $migration): int
    {
        return $this->getVersionFromName($migration->getName());
    }

    /**
     * @param string $name
     * @return int
     */
    protected function getVersionFromName(string $name): int
    {
        $version = strstr($name, '_', true);
        if ($version === false) {
            throw new LogicException('Invalid migration name: ' . $name);
        }
        return (int) $version;
    }
}
