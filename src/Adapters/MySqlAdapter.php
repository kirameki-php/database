<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

use Closure;
use Kirameki\Database\Query\Formatters\MySqlFormatter as MySqlQueryFormatter;
use PDO;
use function array_filter;
use function implode;

class MySqlAdapter extends PdoAdapter
{
    /**
     * @return PDO
     */
    protected function createPdo(): PDO
    {
        $config = $this->getConfig();
        $parts = [];

        if ($config->isNotNull('socket')) {
            $parts[] = "unix_socket={$config->getString('socket')}";
        } else {
            $host = "host={$config->getString('host')}";
            $host.= isset($config['port']) ? "port={$config->getString('port')}" : '';
            $parts[] = $host;
        }

        if ($config->isNotNull('database')) {
            $parts[] = "dbname={$config->getString('database')}";
        }

        if ($config->isNotNull('charset')) {
            $parts[] = "charset={$config->getString('charset')}";
        }

        $dsn = 'mysql:'.implode(';', $parts);
        $username = $config->getStringOrNull('username') ?? 'root';
        $password = $config->getStringOrNull('password');
        $options = (array) ($config->getStringOrNull('options') ?? []);
        $options+= [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
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
     * @return MySqlQueryFormatter
     */
    public function getQueryFormatter(): MySqlQueryFormatter
    {
        return new MySqlQueryFormatter();
    }

    /**
     * @param bool $ifNotExist
     */
    public function createDatabase(bool $ifNotExist = true): void
    {
        $copy = (clone $this);
        $copy->config->set('database', null);
        $copy->execute(implode(' ', array_filter([
            'CREATE DATABASE',
            $ifNotExist ? 'IF NOT EXISTS' : null,
            $this->config->getString('database'),
        ])));
    }

    /**
     * @param bool $ifNotExist
     */
    public function dropDatabase(bool $ifNotExist = true): void
    {
        $copy = (clone $this);
        $copy->config->set('database', null);
        $copy->execute(implode(' ', array_filter([
            'DROP DATABASE',
            $ifNotExist ? 'IF EXISTS' : null,
            $this->config->getString('database'),
        ])));
    }

    /**
     * @return bool
     */
    public function databaseExists(): bool
    {
        $execution = $this->query("SHOW DATABASES LIKE '{$this->config['database']}'");
        $rowCount = $execution->affectedRowCount;
        $trueRowCount = ($rowCount instanceof Closure) ? $rowCount() : $rowCount;
        return $trueRowCount > 0;
    }

    /**
     * @param string $table
     */
    public function truncate(string $table): void
    {
        $this->execute("TRUNCATE TABLE $table");
    }

    /**
     * @return bool
     */
    public function supportsDdlTransaction(): bool
    {
        return false;
    }
}
