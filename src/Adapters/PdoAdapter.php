<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

use Closure;
use Iterator;
use Kirameki\Collections\LazyIterator;
use Kirameki\Core\Exceptions\UnreachableException;
use Kirameki\Database\Config\ConnectionConfig;
use Kirameki\Database\Config\DatabaseConfig;
use Kirameki\Database\Exceptions\ConnectionException;
use Kirameki\Database\Query\Casters\TypeCaster;
use Kirameki\Database\Query\QueryResult;
use Kirameki\Database\Query\Statements\Normalizable;
use Kirameki\Database\Query\Statements\QueryStatement;
use Kirameki\Database\Query\Statements\SelectStatement;
use Kirameki\Database\Query\TypeCastRegistry;
use Kirameki\Database\Schema\SchemaResult;
use Kirameki\Database\Schema\Statements\SchemaStatement;
use Override;
use PDO;
use PDOException;
use PDOStatement;
use function array_map;
use function array_walk;
use function assert;
use function hrtime;

/**
 * @template TConnectionConfig of ConnectionConfig
 * @extends Adapter<TConnectionConfig>
 */
abstract class PdoAdapter extends Adapter
{
    /**
     * @param DatabaseConfig $databaseConfig
     * @param TConnectionConfig $connectionConfig
     * @param TypeCastRegistry|null $casters
     * @param PDO|null $pdo
     */
    public function __construct(
        DatabaseConfig $databaseConfig,
        ConnectionConfig $connectionConfig,
        ?TypeCastRegistry $casters = null,
        protected ?PDO $pdo = null,
    )
    {
        parent::__construct(
            $databaseConfig,
            $connectionConfig,
            $casters,
        );
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
        if ($this->pdo !== null) {
            throw new ConnectionException('Already connected.', $this->connectionConfig);
        }

        try {
            $this->pdo = $this->createPdo();
        } catch (PDOException $e) {
            $this->throwConnectionException($e);
        }

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
            $executables = $statement->toExecutable($this->schemaSyntax);
            array_map($this->getPdo()->exec(...), $executables);
            return $this->instantiateSchemaExecution($statement, $executables, $startTime);
        } catch (PDOException $e) {
            $this->throwSchemaException($e, $statement);
        }
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function runQuery(QueryStatement $statement): QueryResult
    {
        try {
            $syntax = $this->querySyntax;
            $casters = $this->getColumnCasters($statement);

            $template = $statement->generateTemplate($syntax);
            $parameters = $statement->generateParameters($syntax);

            $startTime = hrtime(true);
            $prepared = $this->executeQueryStatement($template, $parameters);
            $rows = $prepared->fetchAll(PDO::FETCH_OBJ);
            $affectedRowCount = $this->getAffectedRows($statement, $prepared);

            if ($statement instanceof Normalizable) {
                $normalized = [];
                foreach ($rows as $row) {
                    $data = $statement->normalize($syntax, $row);
                    if ($data !== null) {
                        $normalized[] = $data;
                    }
                }
                $rows = $normalized;
            }

            if ($casters !== null) {
                array_walk($rows, fn(object $data) => $this->applyCasts($data, $casters));
            }

            return $this->instantiateQueryResult(
                $statement,
                $template,
                $parameters,
                $startTime,
                $rows,
                $affectedRowCount,
            );
        } catch (PDOException $e) {
            $this->throwQueryException($e, $statement);
        }
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function runQueryWithCursor(QueryStatement $statement): QueryResult
    {
        try {
            $syntax = $this->querySyntax;
            $casters = $this->getColumnCasters($statement);

            $template = $statement->generateTemplate($syntax);
            $parameters = $statement->generateParameters($syntax);

            $startTime = hrtime(true);
            $prepared = $this->executeQueryStatement($template, $parameters);
            $iterator = (function() use ($prepared, $statement, $casters, $syntax): Iterator {
                while (true) {
                    $data = $prepared->fetch(PDO::FETCH_OBJ);

                    if ($data === false) {
                        if ($prepared->errorCode() === '00000') {
                            break;
                        }
                        // @codeCoverageIgnoreStart
                        throw new UnreachableException('Failed to fetch data from cursor.', [
                            'errorInfo' => $prepared->errorInfo(),
                            'statement' => $statement,
                        ]);
                        // @codeCoverageIgnoreEnd
                    }

                    if ($statement instanceof Normalizable) {
                        $data = $statement->normalize($syntax, $data);
                        if ($data === null) {
                            continue;
                        }
                    }

                    if ($casters !== null) {
                        $this->applyCasts($data, $casters);
                    }

                    yield $data;
                }
            })();

            return $this->instantiateQueryResult(
                $statement,
                $template,
                $parameters,
                $startTime,
                new LazyIterator($iterator),
                $this->getAffectedRows($statement, $prepared),
            );
        } catch (PDOException $e) {
            $this->throwQueryException($e, $statement);
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
            $syntax = $this->querySyntax;
            $template = 'EXPLAIN ' . $statement->generateTemplate($syntax);
            $parameters = $statement->generateParameters($syntax);
            $prepared = $this->executeQueryStatement($template, $parameters);
            $rows = $prepared->fetchAll(PDO::FETCH_OBJ);
            return $this->instantiateQueryResult($statement, $template, $parameters, $startTime, $rows, 0);
        } catch (PDOException $e) {
            $this->throwQueryException($e, $statement);
        }
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function inTransaction(): bool
    {
        return $this->tryTransactionCall($this->getPdo()->inTransaction(...));
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function commit(): void
    {
        $this->tryTransactionCall($this->getPdo()->commit(...));
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function rollback(): void
    {
        $this->tryTransactionCall($this->getPdo()->rollBack(...));
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
     * @param string $statement
     * @return void
     */
    protected function executeRawStatement(string $statement): void
    {
        $this->getPdo()->exec($statement);
    }

    /**
     * @param QueryStatement $statement
     * @return array<string, TypeCaster>|null
     */
    protected function getColumnCasters(QueryStatement $statement): ?array
    {
        $casts = $statement->casts;

        if ($casts === null) {
            return null;
        }

        $mapped = [];
        $casters = $this->casters;
        foreach ($casts as $key => $type) {
            $mapped[$key] = $casters->getCaster($type);
        }
        return $mapped;
    }

    /**
     * @param object $data
     * @param array<string, TypeCaster> $casters
     * @return void
     */
    protected function applyCasts(object $data, array $casters): void
    {
        foreach ($casters as $key => $caster) {
            $data->$key = $caster->cast($data->$key);
        }
    }

    /**
     * @param QueryStatement $statement
     * @param PDOStatement $prepared
     * @return int
     */
    protected function getAffectedRows(QueryStatement $statement, PDOStatement $prepared): int
    {
        if ($statement instanceof SelectStatement) {
            return 0;
        }
        return $prepared->rowCount();
    }

    /**
     * @template TReturn of mixed
     * @param Closure(): TReturn $callback
     * @return TReturn
     */
    protected function tryTransactionCall(Closure $callback): mixed
    {
        try {
            return $callback();
        } catch (PDOException $e) {
            $this->throwTransactionException($e);
        }
    }
}
