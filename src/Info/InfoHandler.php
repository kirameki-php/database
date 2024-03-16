<?php declare(strict_types=1);

namespace Kirameki\Database\Info;

use Kirameki\Collections\Vec;
use Kirameki\Database\Connection;
use Kirameki\Database\Events\QueryExecuted;
use Kirameki\Database\Execution;
use Kirameki\Database\Query\Statements\QueryExecution;
use Kirameki\Database\Query\Statements\QueryResult;
use Kirameki\Database\Query\Statements\QueryStatement as TStatement;
use Kirameki\Database\Query\Syntax\QuerySyntax;
use Kirameki\Event\EventManager;

readonly class InfoHandler
{
    /**
     * @param Connection $connection
     * @param EventManager $events
     * @param QuerySyntax $syntax
     */
    public function __construct(
        public Connection $connection,
        protected EventManager $events,
        protected QuerySyntax $syntax,
    )
    {
    }

    /**
     * @return Vec<TableInfo>
     */
    public function getTables(): Vec
    {
        $connection = $this->connection;

        $result = $connection->query()->select('TABLE_NAME')
            ->from('INFORMATION_SCHEMA')
            ->where('TABLE_SCHEMA', $connection->adapter->getConfig()->getDatabase())
            ->execute();
    }

    /**
     * @template TStatement of TStatement
     * @param TStatement $statement
     * @return QueryResult<TStatement>
     */
    public function execute(TStatement $statement): QueryResult
    {
        return $this->handleExecution($this->connection->adapter->query($statement));
    }

    /**
     * @template TStatement of TStatement
     * @param TStatement $statement
     * @return QueryResult<TStatement>
     */
    public function cursor(TStatement $statement): QueryResult
    {
        return $this->handleExecution($this->connection->adapter->cursor($statement));
    }

    /**
     * @template TStatement of TStatement
     * @param QueryExecution<TStatement> $execution
     * @return QueryResult<TStatement>
     */
    protected function handleExecution(Execution $execution): QueryResult
    {
        $result = new QueryResult($this->connection, $execution);
        $this->events->emit(new QueryExecuted($result));
        return $result;
    }
}
