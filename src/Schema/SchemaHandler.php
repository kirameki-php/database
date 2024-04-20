<?php declare(strict_types=1);

namespace Kirameki\Database\Schema;

use Kirameki\Database\Connection;
use Kirameki\Database\Events\SchemaExecuted;
use Kirameki\Database\Schema\Statements\SchemaResult;
use Kirameki\Database\Schema\Statements\SchemaStatement;
use Kirameki\Database\Schema\Statements\TruncateTableStatement;
use Kirameki\Event\EventManager;

readonly class SchemaHandler
{
    /**
     * @param Connection $connection
     * @param EventManager $events
     */
    public function __construct(
        public Connection $connection,
        protected EventManager $events,
    )
    {
    }

    /**
     * @param string $table
     */
    public function truncate(string $table): void
    {
        $this->execute(new TruncateTableStatement($table));
    }

    /**
     * @template TSchemaStatement of SchemaStatement
     * @param TSchemaStatement $statement
     * @return SchemaResult<TSchemaStatement>
     */
    public function execute(SchemaStatement $statement): SchemaResult
    {
        $this->preprocess($statement);
        $result = $this->connection->adapter->runSchema($statement);
        return $this->postprocess($result);
    }

    /**
     * @param SchemaStatement $statement
     */
    protected function preprocess(SchemaStatement $statement): void
    {
        $this->connection->connectIfNotConnected();
    }

    /**
     * @template TSchemaStatement of SchemaStatement
     * @param SchemaResult<TSchemaStatement> $result
     * @return SchemaResult<TSchemaStatement>
     */
    protected function postprocess(SchemaResult $result): SchemaResult
    {
        $this->events->emit(new SchemaExecuted($this->connection, $result));
        return $result;
    }
}
