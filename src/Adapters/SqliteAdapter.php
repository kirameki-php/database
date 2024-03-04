<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

use Kirameki\Database\Exceptions\DatabaseNotFoundException;
use Kirameki\Database\Query\Formatters\SqliteFormatter as SqliteQueryFormatter;
use PDO;
use function file_exists;
use function iterator_to_array;
use function unlink;

/**
 * @extends PdoAdapter<SqliteConfig>
 */
class SqliteAdapter extends PdoAdapter
{
    /**
     * @return PDO
     */
    public function createPdo(): PDO
    {
        $config = $this->getConfig();

        $dsn = "sqlite:{$config->filename}";
        $options = iterator_to_array($config->options ?? []);
        $options+= [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
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
     * @inheritDoc
     */
    protected function instantiateQueryFormatter(): SqliteQueryFormatter
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
     * @param bool $ifExist
     * @return void
     */
    public function dropDatabase(bool $ifExist = true): void
    {
        if ($this->databaseExists()) {
            if ($this->isPersistentDatabase()) {
                unlink($this->config->filename);
            }
        } elseif (!$ifExist) {
            throw new DatabaseNotFoundException($this->config->filename, $this->config);
        }

        $this->disconnect();
    }

    /**
     * @return bool
     */
    public function databaseExists(): bool
    {
        // Databases are always considered to exist if connected.
        if ($this->isConnected()) {
            return true;
        }

        $filename = $this->config->filename;

        // In-memory or temporary databases only exist when connected.
        if (!$this->isPersistentDatabase()) {
            return false;
        }

        return file_exists($filename);
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

    /**
     * @return bool
     */
    public function isPersistentDatabase(): bool
    {
        $filename = $this->config->filename;
        return $filename !== ':memory:' && $filename !== '';
    }
}
