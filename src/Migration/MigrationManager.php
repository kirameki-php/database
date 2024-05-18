<?php declare(strict_types=1);

namespace Kirameki\Database\Migration;

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
     * @param DateTimeInterface|null $to
     * @param bool $dryRun
     * @return Vec<SchemaResult<covariant SchemaStatement>>
     */
    public function up(?DateTimeInterface $to = null, bool $dryRun = false): Vec
    {
        $results = [];
        foreach ($this->finder->scanUp($to) as $migration) {
            $results[] = $migration->runUp($dryRun);
        }
        return new Vec(Arr::flatten($results));
    }

    /**
     * @param DateTimeInterface|null $to
     * @param bool $dryRun
     * @return Vec<SchemaResult<covariant SchemaStatement>>
     */
    public function down(?DateTimeInterface $to = null, bool $dryRun = false): Vec
    {
        $results = [];
        foreach ($this->finder->scanDown($to) as $migration) {
            $results[] = $migration->runDown($dryRun);
        }
        return new Vec(Arr::flatten($results));
    }
}
