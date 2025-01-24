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
     */
    public function __construct(
        SchemaHandler $handler,
    )
    {
        parent::__construct($handler, new RenameTableStatement());
    }

    /**
     * @param string $from
     * @param string $to
     * @return static
     */
    public function rename(string $from, string $to): static
    {
        $this->statement->definitions[] = new RenameDefinition($from, $to);
        return $this;
    }
}
