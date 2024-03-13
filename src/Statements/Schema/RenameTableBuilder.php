<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema;

use Kirameki\Database\Connection;

class RenameTableBuilder extends SchemaBuilder
{
    /**
     * @param Connection $connection
     * @param string $from
     * @param string $to
     */
    public function __construct(
        protected Connection $connection,
        protected string $from,
        protected string $to,
    )
    {
        parent::__construct($connection);
    }

    /**
     * @return string[]
     */
    public function build(): array
    {
        $syntax = $this->connection->getSchemaSyntax();
        return [
            $syntax->formatRenameTableStatement($this->from, $this->to),
        ];
    }
}
