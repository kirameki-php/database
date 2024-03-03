<?php declare(strict_types=1);

namespace Kirameki\Database\Migration;

use DateTime;
use DateTimeInterface;
use Kirameki\Collections\Utils\Arr;
use function assert;
use function basename;

class MigrationManager
{
    /**
     * @var string
     */
    protected string $directory;

    /**
     * @param string $directory
     */
    public function __construct(string $directory)
    {
        $this->directory = $directory;
    }

    /**
     * @param DateTime|null $since
     */
    public function up(?DateTime $since = null): void
    {
        foreach ($this->readMigrations($since) as $migration) {
            $migration->up();
            $migration->apply();
        }
    }

    /**
     * @param DateTime|null $since
     */
    public function down(?DateTime $since = null): void
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
                $className = rtrim(ltrim(strstr($file, '_'), '_'), '.php');
                require_once $file;
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
}
