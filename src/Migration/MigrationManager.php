<?php declare(strict_types=1);

namespace Kirameki\Database\Migration;

use DateTime;
use Kirameki\Collections\Arr;

class MigrationManager
{
    /**
     * @var string
     */
    protected string $filePath;

    /**
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->filePath = $path;
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
     * @param DateTime|null $since
     * @return Migration[]
     */
    public function inspectUp(?DateTime $since = null): array
    {
        return $this->inspect('up', $since);
    }

    /**
     * @param DateTime|null $since
     * @return Migration[]
     */
    public function inspectDown(?DateTime $since = null): array
    {
        return $this->inspect('down', $since);
    }

    /**
     * @param string $direction
     * @param DateTime|null $since
     * @return array
     */
    protected function inspect(string $direction, ?DateTime $since = null): array
    {
        $ddls = [];
        foreach ($this->readMigrations($since) as $migration) {
            $migration->$direction();
            $ddls[] = $migration->toDdls();
        }
        return Arr::flatten($ddls);
    }

    /**
     * @param DateTime|null $startAt
     * @return Migration[]
     */
    protected function readMigrations(?DateTime $startAt = null): array
    {
        $start = $startAt ? $startAt->format('YmdHis') : '00000000000000';
        $migrations = [];
        foreach ($this->getMigrationFiles() as $file) {
            $datetime = strstr(class_basename($file), '_', true);
            if ($datetime === null || $datetime >= $start) {
                $className = rtrim(ltrim(strstr($file, '_'), '_'), '.php');
                require_once $file;
                $migrations[] = new $className($datetime);
            }
        }
        return $migrations;
    }

    /**
     * @return array
     */
    protected function getMigrationFiles(): array
    {
        return glob($this->filePath.'/*.php');
    }
}
