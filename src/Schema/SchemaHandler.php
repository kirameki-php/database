<?php declare(strict_types=1);

namespace Kirameki\Database\Schema;

use Kirameki\Database\Connection;
use Kirameki\Database\Events\SchemaExecuted;
use Kirameki\Database\Schema\Statements\SchemaExecution;
use Kirameki\Database\Schema\Statements\SchemaStatement;
use Kirameki\Database\Schema\Statements\TruncateTableStatement;
use Kirameki\Database\Schema\Syntax\SchemaSyntax;
use Kirameki\Event\EventManager;

readonly class SchemaHandler
{
    /**
     * @param Connection $connection
     * @param EventManager $events
     * @param SchemaSyntax $syntax
     */
    public function __construct(
        public Connection $connection,
        protected EventManager $events,
        protected SchemaSyntax $syntax,
    )
    {
    }

    /**
     * @param string $table
     */
    public function truncate(string $table): void
    {
        $this->execute(new TruncateTableStatement($this->syntax, $table));
    }

    /**
     * @template TStatement of SchemaStatement
     * @param TStatement $statement
     * @return SchemaExecution<TStatement>
     */
    public function execute(SchemaStatement $statement): SchemaExecution
    {
        $execution = $this->connection->adapter->runSchema($statement);
        $this->events->emit(new SchemaExecuted($this->connection, $execution));
        return $execution;
    }
}
