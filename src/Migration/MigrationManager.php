<?php declare(strict_types=1);

namespace Kirameki\Database\Migration;

use DateTimeInterface;
use Kirameki\Collections\Utils\Arr;
use function assert;
use function basename;
use function glob;
use function is_a;
use function ltrim;
use function strstr;

class MigrationManager
{
    /**
     * @param string $directory
     */
    public function __construct(
        protected string $directory,
    )
    {
    }

    /**
     * @param DateTimeInterface|null $since
     */
    public function up(?DateTimeInterface $since = null): void
    {
        foreach ($this->readMigrations($since) as $migration) {
            $migration->up();
            $migration->apply();
        }
    }

    /**
     * @param DateTimeInterface|null $since
     */
    public function down(?DateTimeInterface $since = null): void
    {
        foreach ($this->readMigrations($since) as $migration) {
            $migration->down();
            $migration->apply();
        }
    }

    /**
     * @param DateTimeInterface|null $since
     * @return list<string>
     */
    public function inspectUp(?DateTimeInterface $since = null): array
    {
        return $this->inspect('up', $since);
    }

    /**
     * @param DateTimeInterface|null $since
     * @return list<string>
     */
    public function inspectDown(?DateTimeInterface $since = null): array
    {
        return $this->inspect('down', $since);
    }

    /**
     * @param string $direction
     * @param DateTimeInterface|null $since
     * @return list<string>
     */
    protected function inspect(string $direction, ?DateTimeInterface $since = null): array
    {
        $statements = [];
        foreach ($this->readMigrations($since) as $migration) {
            $migration->$direction();
            $statements[] = $migration->toStatements();
        }
        return Arr::flatten($statements);
    }

    /**
     * @param DateTimeInterface|null $startAt
     * @return Migration[]
     */
    protected function readMigrations(?DateTimeInterface $startAt = null): array
    {
        $start = $startAt ? $startAt->format('YmdHis') : '00000000000000';
        $migrations = [];
        foreach ($this->getMigrationFiles() as $file) {
            $datetime = strstr(basename($file), '_', true);
            if ($datetime !== false || $datetime >= $start) {
                require_once $file;
                $className = $this->extractClassName($file);
                $migration = new $className($datetime);
                assert($migration instanceof Migration);
                $migrations[] = $migration;
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
}
