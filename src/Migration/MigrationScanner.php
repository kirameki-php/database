<?php declare(strict_types=1);

namespace Kirameki\Database\Migration;

use Iterator;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\DatabaseManager;
use function basename;
use function glob;
use function is_a;
use function ltrim;
use function rsort;
use function sort;
use function strstr;

readonly class MigrationScanner
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
     * @param ScanDirection $direction
     * @return Iterator<Migration>
     */
    public function scan(ScanDirection $direction): Iterator
    {
        $paths = [];
        foreach ($this->directories as $dir) {
            foreach (glob("{$dir}/*.php") ?: [] as $path) {
                $paths[] = $path;
            }
        }
        match ($direction) {
            ScanDirection::Up => sort($paths),
            ScanDirection::Down => rsort($paths),
        };
        foreach ($paths as $path) {
            yield $this->instantiateClass($path);
        }
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
}
