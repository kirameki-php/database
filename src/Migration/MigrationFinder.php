<?php declare(strict_types=1);

namespace Kirameki\Database\Migration;

use DateTimeInterface;
use Kirameki\Collections\Utils\Arr;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Core\Exceptions\UnreachableException;
use Kirameki\Database\DatabaseManager;
use function basename;
use function glob;
use function is_a;
use function ltrim;
use function rsort;
use function sort;
use function strstr;
use const SORT_ASC;
use const SORT_DESC;

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
     * @param DateTimeInterface|null $to
     * @return list<Migration>
     */
    public function scanUp(?DateTimeInterface $to): array
    {
        $end = $to ? $to->format('YmdHis') : '99999999999999';
        $migrations = [];
        foreach ($this->gatherFilePaths(SORT_ASC) as $datetime => $path) {
            if ($datetime > $end) {
                break;
            }
            $migrations[] = $this->instantiateClass($path);
        }
        return $migrations;
    }

    /**
     * @param DateTimeInterface|null $to
     * @return list<Migration>
     */
    public function scanDown(?DateTimeInterface $to): array
    {
        $end = $to ? $to->format('YmdHis') : '00000000000000';
        $migrations = [];
        foreach ($this->gatherFilePaths(SORT_DESC) as $datetime => $path) {
            if ($datetime < $end) {
                break;
            }
            $migrations[] = $this->instantiateClass($path);
        }
        return $migrations;
    }

    /**
     * @return array<string, string>
     */
    protected function gatherFilePaths(int $order): array
    {
        $paths = [];
        foreach ($this->directories as $dir) {
            foreach (glob("{$dir}/*.php") ?: [] as $path) {
                $paths[] = $path;
            }
        }

        match ($order) {
            SORT_ASC => sort($paths),
            SORT_DESC => rsort($paths),
            default => throw new UnreachableException('Invalid order: ' . $order),
        };

        return Arr::keyBy($paths, $this->extractDateTime(...));
    }

    /**
     * @param string $path
     * @return Migration
     */
    protected function instantiateClass(string $path): Migration
    {
        require_once $path;
        $class = $this->extractClassName($path);
        return new $class($this->db);
    }

    /**
     * @param string $path
     * @return class-string<Migration>
     */
    protected function extractClassName(string $path): string
    {
        $class = basename(ltrim((string) strstr($path, '_'), '_'), '.php');
        if (is_a($class, Migration::class, true)) {
            return $class;
        }
        throw new LogicException($class . ' must extend ' . Migration::class);
    }

    protected function extractDateTime(string $path): string
    {
        $datetime = strstr(basename($path), '_', true);
        if ($datetime === false) {
            throw new LogicException('Invalid migration filename: ' . $path);
        }
        return $datetime;
    }
}
