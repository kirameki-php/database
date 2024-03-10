<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema;

use Kirameki\Database\Connection;
use Kirameki\Database\Statements\OldStatementBuilder;

class RenameTableBuilder implements OldStatementBuilder
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
    }

    /**
     * @return string[]
     */
    public function build(): array
    {
        $formatter = $this->connection->getSchemaFormatter();
        return [
            $formatter->formatRenameTableStatement($this->from, $this->to),
        ];
    }
}