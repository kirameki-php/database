<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Builders;

use Kirameki\Database\Connection;

/**
 * @extends ConditionsBuilder<UpdateStatement>
 */
class UpdateBuilder extends ConditionsBuilder
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
     * @return UpdateStatement
     */
    protected function createStatement(string $table): UpdateStatement
    {
        return new UpdateStatement($table);
    }

    /**
     * @param array<string, mixed> $assignments
     * @return $this
     */
    public function set(array $assignments): static
    {
        $this->statement->data = $assignments;
        return $this;
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
        return $this->formatter->formatUpdateStatement($this->statement);
    }

    /**
     * @return array<mixed>
     */
    public function getBindings(): array
    {
        return $this->formatter->formatBindingsForUpdate($this->statement);
    }
}
