<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements\Table;

use Kirameki\Database\Schema\SchemaHandler;
use Kirameki\Database\Schema\Statements\SchemaBuilder;

/**
 * @extends SchemaBuilder<RenameTableStatement>
 */
class RenameTableBuilder extends SchemaBuilder
{
    /**
     * @param SchemaHandler $handler
     * @param string $from
     * @param string $to
     */
    public function __construct(
        SchemaHandler $handler,
        string $from,
        string $to,
    )
    {
        parent::__construct($handler, new RenameTableStatement($from, $to));
    }
}
