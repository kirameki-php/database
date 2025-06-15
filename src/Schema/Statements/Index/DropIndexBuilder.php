<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements\Index;

use Kirameki\Collections\Utils\Arr;
use Kirameki\Database\Schema\SchemaHandler;
use Kirameki\Database\Schema\Statements\SchemaBuilder;
use function array_values;

/**
 * @extends SchemaBuilder<DropIndexStatement>
 */
class DropIndexBuilder extends SchemaBuilder
{
    /**
     * @param SchemaHandler $handler
     * @param string $table
     * @param array<int, string> $columns
     * @param string|null $name
     */
    public function __construct(
        SchemaHandler $handler,
        string $table,
        array $columns = [],
        ?string $name = null,
    )
    {
        parent::__construct($handler, new DropIndexStatement($table, $columns, $name));
    }
}
