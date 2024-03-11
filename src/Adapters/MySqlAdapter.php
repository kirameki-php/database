<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

use Kirameki\Database\Statements\Query\Syntax\MySqlQuerySyntax;
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
     * @return PDO
     */
    protected function createPdo(): PDO
    {
        $config = $this->getConfig();
        $parts = [];

        if ($config->socket !== null) {
            $parts[] = "unix_socket={$config->socket}";
        } else {
            $host = "host={$config->host}";
            $host.= $config->port !== null ? "port={$config->port}" : '';
            $parts[] = $host;
        }

        if ($config->database !== null) {
            $parts[] = "dbname={$config->database}";
        }

        $dsn = 'mysql:'.implode(';', $parts);
        $username = $config->username ?? 'root';
        $password = $config->password;
        $options = iterator_to_array($config->options ?? []);
        $options+= [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::MYSQL_ATTR_FOUND_ROWS => true,
        ];

        return new PDO($dsn, $username, $password, $options);
    }

    /**
     * @return $this
     */
    public function disconnect(): static
    {
        $this->pdo = null;
        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function instantiateQuerySyntax(): MySqlQuerySyntax
    {
        return new MySqlQuerySyntax($this->identifierDelimiter, $this->literalDelimiter, $this->dateTimeFormat);
    }

    /**
     * @inheritDoc
     */
    public function createDatabase(bool $ifNotExist = false): void
    {
        $copy = (clone $this);
        $copy->config->database = null;
        $copy->execute(implode(' ', array_filter([
            'CREATE DATABASE',
            $ifNotExist ? 'IF NOT EXISTS' : null,
            $this->config->database,
        ])));
    }

    /**
     * @inheritDoc
     */
    public function dropDatabase(bool $ifExist = false): void
    {
        $copy = (clone $this);
        $copy->config->database = null;
        $copy->execute(implode(' ', array_filter([
            'DROP DATABASE',
            $ifExist ? 'IF EXISTS' : null,
            $this->config->database,
        ])));
    }

    /**
     * @inheritDoc
     */
    public function databaseExists(): bool
    {
        $execution = $this->query("SHOW DATABASES LIKE '{$this->config->database}'");
        return $execution->getAffectedRowCount() > 0;
    }

    /**
     * @inheritDoc
     */
    public function truncate(string $table): void
    {
        $this->execute("TRUNCATE TABLE {$table}");
    }

    /**
     * @inheritDoc
     */
    public function supportsDdlTransaction(): bool
    {
        return false;
    }
}
