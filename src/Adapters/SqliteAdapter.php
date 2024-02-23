<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

use Kirameki\Database\Query\Formatters\SqliteFormatter as SqliteQueryFormatter;
use PDO;
use function file_exists;
use function unlink;

class SqliteAdapter extends PdoAdapter
{
    /**
     * @return PDO
     */
    public function createPdo(): PDO
    {
        $config = $this->getConfig();

        $dsn = "sqlite:{$config->getString('path')}";
        $options = (array) ($config['options'] ?? []);
        $options+= [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        return new PDO($dsn, null, null, $options);
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
     * @return SqliteQueryFormatter
     */
    public function getQueryFormatter(): SqliteQueryFormatter
    {
        return new SqliteQueryFormatter();
    }

    /**
     * @param bool $ifNotExist
     * @return void
     */
    public function createDatabase(bool $ifNotExist = true): void
    {
        // nothing necessary
    }

    /**
     * @param bool $ifNotExist
     */
    public function dropDatabase(bool $ifNotExist = true): void
    {
        if ($ifNotExist && !$this->databaseExists()) {
            return;
        }
        unlink($this->config->getString('path'));
    }

    /**
     * @return bool
     */
    public function databaseExists(): bool
    {
        return file_exists($this->config->getString('path'));
    }

    /**
     * @param string $table
     */
    public function truncate(string $table): void
    {
        $this->execute("DELETE FROM $table");
    }

    /**
     * @return bool
     */
    public function supportsDdlTransaction(): bool
    {
        return true;
    }
}
