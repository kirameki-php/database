<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

use Kirameki\Core\Exceptions\InvalidConfigException;
use Kirameki\Database\Config\MySqlConfig;
use Kirameki\Database\Exceptions\DropProtectionException;
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
            throw new InvalidConfigException('Either host or socket must be defined.');
        }
        if ($host !== null && $socket !== null) {
            throw new InvalidConfigException('Host and socket cannot be used together.');
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
        $settings = [
            'SET SESSION TRANSACTION ISOLATION LEVEL ' . $this->connectionConfig->isolationLevel->value,
        ];
        if ($this->connectionConfig->isReadOnly()) {
            $settings[] = 'SET SESSION TRANSACTION READ ONLY';
        }
        $this->getPdo()->exec(implode(';', $settings));
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
        $copy->runSchema(new RawStatement(implode(' ', array_filter([
            'CREATE DATABASE',
            $ifNotExist ? 'IF NOT EXISTS' : null,
            $config->database,
            $config->charset ? 'CHARACTER SET ' . $config->charset : null,
            $config->collation ? 'COLLATE ' . $config->collation : null,
        ]))));

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
            throw new DropProtectionException('Dropping databases are prohibited by configuration.');
        }

        $copy = (clone $this);
        $copy->omitDatabaseOnConnect = true;
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
        $copy->omitDatabaseOnConnect = true;
        $statement = new RawQueryStatement("SHOW DATABASES LIKE '{$this->connectionConfig->database}'");
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
