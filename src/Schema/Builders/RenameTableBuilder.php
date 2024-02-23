<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Builders;

use Kirameki\Database\Connection;

class RenameTableBuilder implements Builder
{
    /**
     * @var Connection
     */
    protected Connection $connection;

    /**
     * @var string
     */
    protected string $from;

    /**
     * @var string
     */
    protected string $to;

    /**
     * @param Connection $connection
     * @param string $from
     * @param string $to
     */
    public function __construct(Connection $connection, string $from, string $to)
    {
        $this->connection = $connection;
        $this->from = $from;
        $this->to = $to;
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