<?php declare(strict_types=1);

namespace Kirameki\Database\Migration;

use Kirameki\Collections\Utils\Arr;
use Kirameki\Collections\Vec;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\DatabaseManager;
use Kirameki\Database\Schema\Statements\SchemaResult;
use Kirameki\Database\Schema\Statements\SchemaStatement;
use ReflectionClass;
use function date;
use function strstr;
use function time;
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
    public function forward(?int $version = null, ?int $steps = null, bool $dryRun = false): Vec
    {
        $version ??= date('YmdHis', time());
        $steps ??= PHP_INT_MAX;
        $results = [];
        foreach ($this->scanner->scan(ScanDirection::Forward) as $migration) {
            if ($steps <= 0) {
                break;
            }
            if ($this->getVersion($migration) > $version) {
                break;
            }
            $results[] = $migration->runForward($dryRun);
            $steps -= 1;
        }
        return new Vec(Arr::flatten($results));
    }

    /**
     * @param int|null $version
     * @param int|null $steps
     * @param bool $dryRun
     * @return Vec<SchemaResult<covariant SchemaStatement>>
     */
    public function backward(?int $version = null, ?int $steps = null, bool $dryRun = false): Vec
    {
        $version ??= '00000000000000';
        $steps ??= PHP_INT_MAX;
        $results = [];
        foreach ($this->scanner->scan(ScanDirection::Backward) as $migration) {
            if ($steps <= 0) {
                break;
            }
            if ($this->getVersion($migration) < $version) {
                break;
            }
            $results[] = $migration->runForward($dryRun);
            $steps -= 1;
        }
        return new Vec(Arr::flatten($results));
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
