<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

use Kirameki\Database\Config\MySqlConfig;
use Kirameki\Database\Query\Statements\RawStatement as RawQueryStatement;
use Kirameki\Database\Query\Syntax\MySqlQuerySyntax;
use Kirameki\Database\Schema\Statements\RawStatement;
use Kirameki\Database\Schema\Syntax\MySqlSchemaSyntax;
use Kirameki\Database\Schema\Syntax\SchemaSyntax;
use Kirameki\Database\Transaction\Support\IsolationLevel;
use Override;
use PDO;
use function array_filter;
use function implode;
use function iterator_to_array;

/**
 * @extends PdoAdapter<MySqlConfig>
 */
class MySqlAdapter extends PdoAdapter
{
    protected string $identifierDelimiter = '`';

    protected string $literalDelimiter = '"';

    protected string $dateTimeFormat = 'Y-m-d H:i:s.u';

    /**
     * @inheritDoc
     */
    #[Override]
    protected function createPdo(): PDO
    {
        $config = $this->getConnectionConfig();
        $parts = [];

        if ($config->socket !== null) {
            $parts[] = "unix_socket={$config->socket}";
        } else {
            $host = "host={$config->host}";
            $host .= $config->port !== null ? "port={$config->port}" : '';
            $parts[] = $host;
        }

        if ($config->database !== null) {
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
        $settings = [
            'SET SESSION TRANSACTION ISOLATION LEVEL ' . $this->connectionConfig->isolationLevel->value,
        ];
        if ($this->readonly) {
            $settings[] = 'SET SESSION TRANSACTION READ ONLY';
        }
        if ($settings !== []) {
            $this->getPdo()->exec(implode(';', $settings));
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
    public function createDatabase(bool $ifNotExist = false): void
    {
        $config= $this->connectionConfig;
        $copy = (clone $this);
        $copy->connectionConfig->database = null;
        $copy->runSchema(new RawStatement(implode(' ', array_filter([
            'CREATE DATABASE',
            $ifNotExist ? 'IF NOT EXISTS' : null,
            $config->database,
            $config->charset ? 'CHARACTER SET ' . $config->charset : null,
            $config->collation ? 'COLLATE ' . $config->collation : null,
        ]))));
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function dropDatabase(bool $ifExist = false): void
    {
        $copy = (clone $this);
        $copy->connectionConfig->database = null;
        $copy->runSchema(new RawStatement(implode(' ', array_filter([
            'DROP DATABASE',
            $ifExist ? 'IF EXISTS' : null,
            $this->connectionConfig->database,
        ]))));
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function databaseExists(): bool
    {
        $copy = (clone $this);
        $copy->connectionConfig->database = null;
        $statement = new RawQueryStatement(null, "SHOW DATABASES LIKE '{$this->connectionConfig->database}'");
        return $copy->runQuery($statement)->getAffectedRowCount() > 0;
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
}
