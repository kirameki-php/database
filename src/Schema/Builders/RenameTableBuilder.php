<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Builders;

use Kirameki\Database\Connection;

class RenameTableBuilder implements Builder
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