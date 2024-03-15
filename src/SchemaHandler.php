<?php declare(strict_types=1);

namespace Kirameki\Database;

use Kirameki\Database\Events\SchemaExecuted;
use Kirameki\Database\Statements\Execution;
use Kirameki\Database\Statements\Schema\SchemaExecution;
use Kirameki\Database\Statements\Schema\SchemaStatement;
use Kirameki\Database\Statements\Schema\Syntax\SchemaSyntax;
use Kirameki\Database\Statements\Schema\TruncateTableStatement;
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
    public function execute(SchemaStatement $statement): Execution
    {
        $execution = $this->connection->adapter->runSchema($statement);
        $this->events->emit(new SchemaExecuted($this->connection, $execution));
        return $execution;
    }
}
