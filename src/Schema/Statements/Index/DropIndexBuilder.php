<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements\Index;

use Kirameki\Collections\Utils\Arr;
use Kirameki\Database\Schema\SchemaHandler;
use Kirameki\Database\Schema\Statements\SchemaBuilder;

/**
 * @extends SchemaBuilder<DropIndexStatement>
 */
class DropIndexBuilder extends SchemaBuilder
{
    /**
     * @param string $table
     * @param iterable<int, string> $columns
     * @return string
     */
    public static function deriveName(string $table, iterable $columns): string
    {
        return implode('_', Arr::merge([$table], $columns));
    }

    /**
     * @param SchemaHandler $handler
     * @param string $table
     * @param string $name
     */
    public function __construct(
        SchemaHandler $handler,
        string $table,
        string $name,
    )
    {
        parent::__construct($handler, new DropIndexStatement($table, $name));
    }
}
