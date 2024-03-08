<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Builders;

use Kirameki\Database\Connection;

/**
 * @extends ConditionsBuilder<DeleteStatement>
 */
class DeleteBuilder extends ConditionsBuilder
{
    /**
     * @param Connection $connection
     * @param string $table
     */
    public function __construct(
        Connection $connection,
        string $table,
    )
    {
        parent::__construct($connection, $this->createStatement($table));
    }

    /**
     * @param string $table
     * @return DeleteStatement
     */
    protected function createStatement(string $table): DeleteStatement
    {
        return new DeleteStatement($table);
    }

    /**
     * @param string ...$columns
     * @return $this
     */
    public function returning(string ...$columns): static
    {
        $this->statement->returning = $columns;
        return $this;
    }

    /**
     * @return string
     */
    public function prepare(): string
    {
        return $this->formatter->formatDeleteStatement($this->statement);
    }

    /**
     * @return array<mixed>
     */
    public function getBindings(): array
    {
        return $this->formatter->formatBindingsForDelete($this->statement);
    }
}
