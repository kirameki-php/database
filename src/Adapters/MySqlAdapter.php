<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

use Kirameki\Core\Exceptions\InvalidConfigException;
use Kirameki\Database\Config\MySqlConfig;
use Kirameki\Database\Exceptions\DatabaseExistsException;
use Kirameki\Database\Exceptions\DatabaseNotFoundException;
use Kirameki\Database\Exceptions\DropProtectionException;
use Kirameki\Database\Exceptions\SchemaException;
use Kirameki\Database\Query\Statements\RawStatement as RawQueryStatement;
use Kirameki\Database\Query\Syntax\MySqlQuerySyntax;
use Kirameki\Database\Schema\Statements\RawStatement;
use Kirameki\Database\Schema\Syntax\MySqlSchemaSyntax;
use Kirameki\Database\Schema\Syntax\SchemaSyntax;
use Kirameki\Database\Transaction\TransactionOptions;
use Override;
use PDO;
use PDOException;
use function array_filter;
use function implode;
use function iterator_to_array;
use function str_ends_with;

/**
 * @extends PdoAdapter<MySqlConfig>
 */
class MySqlAdapter extends PdoAdapter
{
    /**
     * @var string
     */
    protected string $identifierDelimiter = '`';

    /**
     * @var string
     */
    protected string $literalDelimiter = '"';

    /**
     * @var string
     */
    protected string $dateTimeFormat = 'Y-m-d H:i:s.u';

    /**
     * @var bool
     */
    protected bool $omitDatabaseOnConnect = false;

    /**
     * @inheritDoc
     */
    #[Override]
    protected function createPdo(): PDO
    {
        $config = $this->connectionConfig;
        $parts = [];

        $host = $config->host;
        $socket = $config->socket;
        if ($host === null && $socket === null) {
            throw new InvalidConfigException('Either host or socket must be defined.', [
                'adapter' => $this,
            ]);
        }
        if ($host !== null && $socket !== null) {
            throw new InvalidConfigException('Host and socket cannot be used together.', [
                'adapter' => $this,
            ]);
        }
        if ($socket !== null) {
            $parts[] = "unix_socket={$config->socket}";
        } else {
            $parts[] = "host={$config->host}";
            if ($config->port !== null) {
                $parts[] = "port={$config->port}";
            }
        }

        if (!$this->omitDatabaseOnConnect) {
            $parts[] = "dbname={$config->database}";
        }

        $dsn = 'mysql:' . implode(';', $parts);
        $username = $config->username ?? 'root';
        $password = $config->password;
        $options = iterator_to_array($config->serverOptions ?? []);
        $options += [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_TIMEOUT => $config->connectTimeoutSeconds,
            PDO::MYSQL_ATTR_FOUND_ROWS => true,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
        ];

        return new PDO($dsn, $username, $password, $options);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function connect(): static
    {
        parent::connect();

        $connectionConfig = $this->connectionConfig;

        $setTransaction = 'SET SESSION TRANSACTION ISOLATION LEVEL '
            . $connectionConfig->isolationLevel->value
            . ($connectionConfig->isReadOnly() ? ', READ ONLY' : '');

        $setOptions = [];
        if ($connectionConfig->transactionLockWaitTimeoutSeconds) {
            $setOptions[] = 'innodb_lock_wait_timeout=' . $connectionConfig->transactionLockWaitTimeoutSeconds;
        }

        try {
            $this->executeRawStatement($setTransaction);
            if ($setOptions !== []) {
                $this->executeRawStatement('SET ' . implode(',', $setOptions));
            }
        } catch (PDOException $e) {
            $this->throwConnectionException($e);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function instantiateQuerySyntax(): MySqlQuerySyntax
    {
        return new MySqlQuerySyntax(
            $this->databaseConfig,
            $this->connectionConfig,
            $this->identifierDelimiter,
            $this->literalDelimiter,
            $this->dateTimeFormat,
        );
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function instantiateSchemaSyntax(): SchemaSyntax
    {
        return new MySqlSchemaSyntax(
            $this->databaseConfig,
            $this->connectionConfig,
            $this->identifierDelimiter,
            $this->literalDelimiter,
            $this->dateTimeFormat,
        );
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function createDatabase(bool $ifNotExist = true): void
    {
        $config = $this->connectionConfig;
        $copy = (clone $this);
        $copy->omitDatabaseOnConnect = true;
        $database = $config->database;

        try {
            $copy->runSchema(new RawStatement(implode(' ', array_filter([
                'CREATE DATABASE',
                $ifNotExist ? 'IF NOT EXISTS' : null,
                $database,
                $config->charset ? "CHARACTER SET {$config->charset}" : null,
                $config->collation ? "COLLATE {$config->collation}"  : null,
            ]))));
        } catch (SchemaException $e) {
            if (str_ends_with($e->getMessage(), "1007 Can't create database '{$database}'; database exists")) {
                throw new DatabaseExistsException($database, ['adapter' => $this]);
            }
        }

        // attempt to reconnect to the created database
        $this->getPdo();
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function dropDatabase(bool $ifExist = true): void
    {
        if ($this->databaseConfig->dropProtection) {
            $database = $this->connectionConfig->database;
            throw new DropProtectionException("Dropping database '{$database}' is prohibited.", [
                'adapter' => $this,
            ]);
        }

        $copy = (clone $this);
        $copy->omitDatabaseOnConnect = true;
        $database = $this->connectionConfig->database;

        try {
            $copy->runSchema(new RawStatement(implode(' ', array_filter([
                'DROP DATABASE',
                $ifExist ? 'IF EXISTS' : null,
                $database,
            ]))));
        } catch (SchemaException $e) {
            if (str_ends_with($e->getMessage(), "1008 Can't drop database '{$database}'; database doesn't exist")) {
                throw new DatabaseNotFoundException($database, ['adapter' => $this]);
            }
        }
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function databaseExists(): bool
    {
        $copy = (clone $this);
        $copy->omitDatabaseOnConnect = true;
        $statement = new RawQueryStatement("SHOW DATABASES LIKE '{$this->connectionConfig->database}'");
        return $copy->runQuery($statement)->getAffectedRowCount() > 0;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function beginTransaction(?TransactionOptions $options = null): void
    {
        $level = $options?->isolationLevel;

        $this->tryTransactionCall(function() use ($level) {
            if ($level !== null) {
                $this->executeRawStatement("SET TRANSACTION ISOLATION LEVEL {$level->value}");
            }
            $this->getPdo()->beginTransaction();
        });
    }
}
