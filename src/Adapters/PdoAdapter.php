<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

use Iterator;
use Kirameki\Collections\LazyIterator;
use Kirameki\Database\Config\ConnectionConfig;
use Kirameki\Database\Config\DatabaseConfig;
use Kirameki\Database\Exceptions\QueryException;
use Kirameki\Database\Exceptions\SchemaException;
use Kirameki\Database\Query\Statements\Normalizable;
use Kirameki\Database\Query\Statements\QueryResult;
use Kirameki\Database\Query\Statements\QueryStatement;
use Kirameki\Database\Query\Syntax\QuerySyntax;
use Kirameki\Database\Schema\Statements\SchemaResult;
use Kirameki\Database\Schema\Statements\SchemaStatement;
use Kirameki\Database\Schema\Syntax\SchemaSyntax;
use Kirameki\Database\Transaction\Support\IsolationLevel;
use Override;
use PDO;
use PDOException;
use PDOStatement;
use function assert;
use function hrtime;
use function implode;
use function iterator_to_array;

/**
 * @template TConfig of ConnectionConfig
 * @extends Adapter<TConfig>
 */
abstract class PdoAdapter extends Adapter
{
    /**
     * @param DatabaseConfig $databaseConfig
     * @param TConfig $connectionConfig
     * @param QuerySyntax|null $querySyntax
     * @param SchemaSyntax|null $schemaSyntax
     * @param PDO|null $pdo
     */
    public function __construct(
        DatabaseConfig $databaseConfig,
        ConnectionConfig $connectionConfig,
        ?QuerySyntax $querySyntax = null,
        ?SchemaSyntax $schemaSyntax = null,
        protected ?PDO $pdo = null,
    )
    {
        parent::__construct($databaseConfig, $connectionConfig, $querySyntax, $schemaSyntax);
    }

    /**
     * @return void
     */
    public function __clone(): void
    {
        $this->connectionConfig = clone $this->connectionConfig;
    }

    /**
     * @return PDO
     */
    protected function getPdo(): PDO
    {
        if ($this->pdo !== null) {
            return $this->pdo;
        }
        $this->connect();
        assert($this->pdo !== null);
        return $this->pdo;
    }

    /**
     * @return PDO
     */
    abstract protected function createPdo(): PDO;

    /**
     * @inheritDoc
     */
    #[Override]
    public function connect(): static
    {
        $this->pdo = $this->createPdo();
        return $this;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function disconnect(): static
    {
        $this->pdo = null;
        return $this;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function isConnected(): bool
    {
        return $this->pdo !== null;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function runSchema(SchemaStatement $statement): SchemaResult
    {
        try {
            $startTime = hrtime(true);
            $executables = $statement->toExecutable($this->getSchemaSyntax());
            $this->getPdo()->exec(implode('; ', $executables));
            return $this->instantiateSchemaExecution($statement, $executables, $startTime);
        } catch (PDOException $e) {
            throw new SchemaException($e->getMessage(), $statement, $e);
        }
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function runQuery(QueryStatement $statement): QueryResult
    {
        try {
            $startTime = hrtime(true);
            $syntax = $this->getQuerySyntax();
            $template = $statement->generateTemplate($syntax);
            $parameters = $statement->generateParameters($syntax);
            $prepared = $this->executeQueryStatement($template, $parameters);
            $rows = $prepared->fetchAll(PDO::FETCH_OBJ);
            if ($statement instanceof Normalizable) {
                $rows = iterator_to_array($statement->normalize($syntax, $rows));
            }
            $count = $prepared->rowCount(...);
            return $this->instantiateQueryResult($statement, $template, $parameters, $startTime, $rows, $count);
        } catch (PDOException $e) {
            throw new QueryException($e->getMessage(), $statement, null, $e);
        }
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function runQueryWithCursor(QueryStatement $statement): QueryResult
    {
        try {
            $startTime = hrtime(true);
            $syntax = $this->getQuerySyntax();
            $template = $statement->generateTemplate($syntax);
            $parameters = $statement->generateParameters($syntax);
            $prepared = $this->executeQueryStatement($template, $parameters);
            $iterator = (function() use ($prepared, $statement): Iterator {
                while (true) {
                    $data = $prepared->fetch(PDO::FETCH_OBJ);
                    if ($data === false) {
                        if ($prepared->errorCode() === '00000') {
                            break;
                        }
                        $this->throwQueryException($prepared, $statement);
                    }
                    yield $data;
                }
            })();
            if ($statement instanceof Normalizable) {
                $iterator = $statement->normalize($syntax, $iterator);
            }
            $rows = new LazyIterator($iterator);
            $count = $prepared->rowCount(...);
            return $this->instantiateQueryResult($statement, $template, $parameters, $startTime, $rows, $count);
        } catch (PDOException $e) {
            throw new QueryException($e->getMessage(), $statement, null, $e);
        }
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function explainQuery(QueryStatement $statement): QueryResult
    {
        try {
            $startTime = hrtime(true);
            $syntax = $this->getQuerySyntax();
            $template = 'EXPLAIN ' . $statement->generateTemplate($syntax);
            $parameters = $statement->generateParameters($syntax);
            $prepared = $this->executeQueryStatement($template, $parameters);
            $rows = $prepared->fetchAll(PDO::FETCH_OBJ);
            return $this->instantiateQueryResult($statement, $template, $parameters, $startTime, $rows, 0);
        } catch (PDOException $e) {
            throw new QueryException($e->getMessage(), $statement, null, $e);
        }
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function beginTransaction(?IsolationLevel $level = null): void
    {
        if ($level !== null) {
            $this->getPdo()->exec('SET TRANSACTION ISOLATION LEVEL ' . $level->value);
        }
        $this->getPdo()->beginTransaction();
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function commit(): void
    {
        $this->getPdo()->commit();
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function rollback(): void
    {
        $this->getPdo()->rollBack();
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function inTransaction(): bool
    {
        return $this->getPdo()->inTransaction();
    }

    /**
     * @param string $template
     * @param list<mixed> $parameters
     * @return PDOStatement
     */
    protected function executeQueryStatement(string $template, array $parameters): PDOStatement
    {
        $prepared = $this->getPdo()->prepare($template);
        $prepared->execute($parameters);
        return $prepared;
    }

    /**
     * @param PDOStatement $prepared
     * @param QueryStatement $statement
     * @return void
     */
    protected function throwQueryException(PDOStatement $prepared, QueryStatement $statement): void
    {
        throw new QueryException(implode(' | ', $prepared->errorInfo()), $statement);
    }
}
