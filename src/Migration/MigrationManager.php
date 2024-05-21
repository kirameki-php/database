<?php declare(strict_types=1);

namespace Kirameki\Database\Migration;

use Kirameki\Collections\Utils\Arr;
use Kirameki\Collections\Vec;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\DatabaseManager;
use Kirameki\Database\Schema\Statements\SchemaResult;
use Kirameki\Database\Schema\Statements\SchemaStatement;
use ReflectionClass;
use function strstr;
use const PHP_INT_MAX;

readonly class MigrationManager
{
    /**
     * @param DatabaseManager $db
     * @param MigrationScanner $scanner
     */
    public function __construct(
        protected DatabaseManager $db,
        protected MigrationScanner $scanner,
    )
    {
    }

    /**
     * @param int|null $version
     * @param int|null $steps
     * @param bool $dryRun
     * @return Vec<SchemaResult<covariant SchemaStatement>>
     */
    public function up(?int $version = null, ?int $steps = null, bool $dryRun = false): Vec
    {
        return new Vec($this->run(ScanDirection::Up, $version, $steps, $dryRun));
    }

    /**
     * @param int|null $version
     * @param int|null $steps
     * @param bool $dryRun
     * @return Vec<SchemaResult<covariant SchemaStatement>>
     */
    public function down(?int $version = null, ?int $steps = null, bool $dryRun = false): Vec
    {
        return new Vec($this->run(ScanDirection::Down, $version, $steps, $dryRun));
    }

    /**
     * @param ScanDirection $direction
     * @param int|null $version
     * @param int|null $steps
     * @param bool $dryRun
     * @return list<SchemaResult<covariant SchemaStatement>>
     */
    protected function run(
        ScanDirection $direction,
        ?int $version,
        ?int $steps,
        bool $dryRun = false,
    ): array
    {
        $version ??= date('YmdHis', time());
        $steps ??= PHP_INT_MAX;
        $results = [];
        foreach ($this->scanner->scan($direction) as $migration) {
            if ($steps <= 0) {
                break;
            }
            if ($this->getVersion($migration) >= $version) {
                break;
            }
            $results[] = $migration->runUp($dryRun);
            $steps -= 1;
        }
        return Arr::flatten($results);
    }

    /**
     * @param Migration $migration
     * @return int
     */
    protected function getVersion(Migration $migration): int
    {
        $reflection = new ReflectionClass($migration);
        $version = strstr($reflection->getShortName(), '_', true);
        if ($version === false) {
            throw new LogicException('Invalid migration format: ' . static::class);
        }
        return (int) $version;
    }
}
