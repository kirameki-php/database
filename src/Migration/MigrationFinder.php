<?php declare(strict_types=1);

namespace Kirameki\Database\Migration;

use DateTimeInterface;
use Kirameki\Collections\Utils\Arr;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\DatabaseManager;
use function basename;
use function glob;
use function is_a;
use function ltrim;
use function sort;
use function strstr;

readonly class MigrationFinder
{
    /**
     * @param DatabaseManager $db
     * @param list<string> $directories
     */
    public function __construct(
        protected DatabaseManager $db,
        protected array $directories,
    )
    {
    }

    /**
     * @param DateTimeInterface|null $startAt
     * @param bool $dryRun
     * @return array<string, Migration>
     */
    public function scan(?DateTimeInterface $startAt, bool $dryRun = false): array
    {
        $start = $startAt ? $startAt->format('YmdHis') : '00000000000000';
        $migrations = [];
        foreach ($this->gatherFiles() as $file) {
            $datetime = strstr(basename($file), '_', true);
            if ($datetime !== false && $datetime >= $start) {
                $migrations[$datetime] = $this->instantiateClass($file, $dryRun);
            }
        }
        return $migrations;
    }

    /**
     * @return list<string>
     */
    protected function gatherFiles(): array
    {
        $files = [];
        foreach ($this->directories as $dir) {
            $files[] = glob("{$dir}/*.php") ?: [];
        }
        sort($files);
        return Arr::flatten($files);
    }

    /**
     * @param string $file
     * @param bool $dryRun
     * @return Migration
     */
    protected function instantiateClass(string $file, bool $dryRun): Migration
    {
        require_once $file;
        $class = $this->extractClassName($file);
        return new $class($this->db, $dryRun);
    }

    /**
     * @param string $file
     * @return class-string<Migration>
     */
    protected function extractClassName(string $file): string
    {
        $class = basename(ltrim((string) strstr($file, '_'), '_'), '.php');
        if (is_a($class, Migration::class, true)) {
            return $class;
        }
        throw new LogicException($class . ' must extend ' . Migration::class);
    }
}
