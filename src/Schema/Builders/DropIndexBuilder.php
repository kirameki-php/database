<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Builders;

use Kirameki\Collections\Arr;
use Kirameki\Database\Connection;
use Kirameki\Database\Schema\Statements\DropIndexStatement;
use RuntimeException;

/**
 * @property DropIndexStatement $statement
 */
class DropIndexBuilder extends StatementBuilder
{
    /**
     * @param Connection $connection
     * @param DropIndexStatement $statement
     */
    public function __construct(Connection $connection, DropIndexStatement $statement)
    {
        $this->connection = $connection;
        $this->statement = $statement;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function name(string $name): static
    {
        $this->statement->name = $name;
        return $this;
    }

    /**
     * @param string $column
     * @return $this
     */
    public function column(string $column): static
    {
        $this->statement->columns[] = $column;
        return $this;
    }

    /**
     * @param string|string[] $columns
     * @return $this
     */
    public function columns(string|array $columns): static
    {
        foreach (Arr::wrap($columns) as $column) {
            $this->column($column);
        }
        return $this;
    }

    /**
     * @return string[]
     */
    public function build(): array
    {
        $this->preprocess();
        $formatter = $this->connection->getSchemaFormatter();
        return [
            $formatter->formatDropIndexStatement($this->statement)
        ];
    }

    /**
     * @return void
     */
    public function preprocess(): void
    {
        $name = $this->statement->name;
        $columns = $this->statement->columns;

        if($name === null && empty($columns)) {
            throw new RuntimeException('No name or columns required to drop an index.');
        }
    }
}
