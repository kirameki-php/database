<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

use Closure;
use DateTimeInterface;
use Iterator;
use Kirameki\Database\Statements\Query\QueryExecution;
use Kirameki\Database\Statements\Query\QueryStatement;
use Kirameki\Database\Statements\Query\RawStatement;
use Kirameki\Database\Statements\Query\Syntax\QuerySyntax;
use Kirameki\Database\Statements\Schema\SchemaExecution;
use Kirameki\Database\Statements\Schema\SchemaStatement;
use Kirameki\Database\Statements\Schema\Syntax\SchemaSyntax;
use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;
use function dump;
use function hrtime;
use function implode;

/**
 * @template TConfig of DatabaseConfig
 */
abstract class PdoAdapter implements DatabaseAdapter
{
    protected string $identifierDelimiter = '"';

    protected string $literalDelimiter = "'";

    protected string $dateTimeFormat = DateTimeInterface::RFC3339_EXTENDED;

    /**
     * @param TConfig $config
     * @param PDO|null $pdo
     * @param QuerySyntax|null $querySyntax
     * @param SchemaSyntax|null $schemaSyntax
     */
    public function __construct(
        protected DatabaseConfig $config,
        protected ?PDO $pdo = null,
        protected ?QuerySyntax $querySyntax = null,
        protected ?SchemaSyntax $schemaSyntax = null,
    )
    {
    }

    /**
     * @return void
     */
    public function __clone(): void
    {
        $this->config = clone $this->config;
    }

    /**
     * @inheritDoc
     * @return TConfig
     */
    public function getConfig(): DatabaseConfig
    {
        return $this->config;
    }

    /**
     * @inheritDoc
     */
    public function connect(): static
    {
        $this->pdo = $this->createPdo();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isConnected(): bool
    {
        return $this->pdo !== null;
    }

    /**
     * @inheritDoc
     */
    public function runSchema(SchemaStatement $statement): SchemaExecution
    {
        $startTime = hrtime(true);
        foreach ($statement->prepare() as $schema) {
            $this->getPdo()->exec($schema);
        }
        $execTimeMs = (hrtime(true) - $startTime) / 1_000_000;
        return $this->instantiateSchemaExecution($statement, $execTimeMs);
    }

    /**
     * @inheritDoc
     */
    public function query(QueryStatement $statement): QueryExecution
    {
        $startTime = hrtime(true);
        $prepared = $this->execQuery($statement);
        $rows = $prepared->fetchAll(PDO::FETCH_OBJ);
        $fetchTimeMs = (hrtime(true) - $startTime) / 1_000_000;
        $count = $prepared->rowCount(...);
        return $this->instantiateQueryExecution($statement, $fetchTimeMs, $rows, $count);
    }

    /**
     * @inheritDoc
     */
    public function cursor(QueryStatement $statement): QueryExecution
    {
        $startTime = hrtime(true);
        $prepared = $this->execQuery($statement);
        $iterator = (function() use ($prepared): Iterator {
            while (true) {
                $data = $prepared->fetch(PDO::FETCH_OBJ);
                if ($data === false) {
                    if ($prepared->errorCode() === '00000') {
                        break;
                    }
                    $this->throwException($prepared);
                }
                yield $data;
            }
        })();
        $execTimeMs = (hrtime(true) - $startTime) / 1_000_000;
        $count = $prepared->rowCount(...);
        return $this->instantiateQueryExecution($statement, $execTimeMs, $iterator, $count);
    }

    /**
     * @return void
     */
    public function beginTransaction(): void
    {
        $this->getPdo()->beginTransaction();
    }

    /**
     * @return void
     */
    public function commit(): void
    {
        $this->getPdo()->commit();
    }

    /**
     * @inheritDoc
     */
    public function rollback(): void
    {
        $this->getPdo()->rollBack();
    }

    /**
     * @inheritDoc
     */
    public function inTransaction(): bool
    {
        return $this->getPdo()->inTransaction();
    }

    public function tableExists(string $table): bool
    {
        try {
            $syntax = $this->getQuerySyntax();
            $table = $syntax->asIdentifier($table);
            $statement = new RawStatement($syntax, "SELECT 1 FROM {$table} LIMIT 1");
            $this->query($statement);
            return true;
        } catch (PDOException) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function getQuerySyntax(): QuerySyntax
    {
        return $this->querySyntax ??= $this->instantiateQuerySyntax();
    }

    /**
     * @return QuerySyntax
     */
    abstract protected function instantiateQuerySyntax(): QuerySyntax;

    /**
     * @inheritDoc
     */
    public function getSchemaSyntax(): SchemaSyntax
    {
        return $this->schemaSyntax ??= $this->instantiateSchemaSyntax();
    }

    /**
     * @return SchemaSyntax
     */
    protected function instantiateSchemaSyntax(): SchemaSyntax
    {
        return new SchemaSyntax(
            $this->identifierDelimiter,
            $this->literalDelimiter,
            $this->dateTimeFormat
        );
    }

    /**
     * @param QueryStatement $statement
     * @return PDOStatement
     */
    protected function execQuery(QueryStatement $statement): PDOStatement
    {
        $prepared = $this->getPdo()->prepare($statement->prepare());
        $prepared->execute($statement->getParameters());
        return $prepared;
    }

    /**
     * @return PDO
     */
    protected function getPdo(): PDO
    {
        if ($this->pdo === null) {
            $this->pdo = $this->createPdo();
        }
        return $this->pdo;
    }

    abstract protected function createPdo(): PDO;

    /**
     * @template TStatement of SchemaStatement
     * @param TStatement $statement
     * @param float $elapsedMs
     * @return SchemaExecution<TStatement>
     */
    protected function instantiateSchemaExecution(
        SchemaStatement $statement,
        float $elapsedMs,
    ): SchemaExecution
    {
        return new SchemaExecution($statement, $elapsedMs);
    }

    /**
     * @template TStatement of QueryStatement
     * @param TStatement $statement
     * @param float $elapsedMs
     * @param iterable<int, mixed> $rowIterator
     * @param int|Closure(): int $affectedRowCount
     * @return QueryExecution<TStatement>
     */
    protected function instantiateQueryExecution(
        QueryStatement $statement,
        float $elapsedMs,
        iterable $rowIterator,
        int|Closure $affectedRowCount,
    ): QueryExecution
    {
        return new QueryExecution($statement, $elapsedMs, $rowIterator, $affectedRowCount);
    }

    /**
     * @param PDOStatement $statement
     * @return void
     */
    protected function throwException(PDOStatement $statement): void
    {
        throw new RuntimeException(implode(' | ', $statement->errorInfo()));
    }
}
