<?php declare(strict_types=1);

namespace Kirameki\Database;

use Kirameki\Database\Adapters\DatabaseAdapter;
use Kirameki\Database\Events\SchemaExecuted;
use Kirameki\Database\Statements\Execution;
use Kirameki\Database\Statements\Schema\SchemaExecution;
use Kirameki\Database\Statements\Schema\SchemaStatement;
use Kirameki\Database\Statements\Schema\Syntax\SchemaSyntax;
use Kirameki\Event\EventManager;

readonly class SchemaHandler
{
    /**
     * @var DatabaseAdapter
     */
    protected DatabaseAdapter $adapter;

    /**
     * @var SchemaSyntax
     */
    protected SchemaSyntax $syntax;

    /**
     * @param Connection $connection
     * @param EventManager $events
     */
    public function __construct(
        protected Connection $connection,
        protected EventManager $events,
    )
    {
        $this->adapter = $connection->getAdapter();
        $this->syntax = $this->adapter->getSchemaSyntax();
    }

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @param string $table
     * @return bool
     */
    public function tableExists(string $table): bool
    {
        return $this->connection->getAdapter()->tableExists($table);
    }

    /**
     * @param string $table
     */
    public function truncate(string $table): void
    {
        $this->connection->getAdapter()->truncate($table);
    }

    /**
     * @template TStatement of SchemaStatement
     * @param TStatement $statement
     * @return SchemaExecution<TStatement>
     */
    public function execute(SchemaStatement $statement): Execution
    {
        $execution = $this->connection->getAdapter()->runSchema($statement);
        $this->events->emit(new SchemaExecuted($this->connection, $execution));
        return $execution;
    }
}
