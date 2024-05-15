<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

use Kirameki\Database\Exceptions\DatabaseNotFoundException;
use Kirameki\Database\Query\Syntax\SqliteQuerySyntax;
use Kirameki\Database\Schema\Syntax\SqliteSchemaSyntax;
use Override;
use PDO;
use function file_exists;
use function implode;
use function iterator_to_array;
use function unlink;

/**
 * @extends PdoAdapter<SqliteConfig>
 */
class SqliteAdapter extends PdoAdapter
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function createPdo(): PDO
    {
        $config = $this->getConfig();

        $dsn = "sqlite:{$config->filename}";
        $options = iterator_to_array($config->options ?? []);
        $options += [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_TIMEOUT => $config->busyTimeoutSeconds,
        ];

        return new PDO($dsn, null, null, $options);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function connect(): static
    {
        parent::connect();
        $settings = [
            'PRAGMA foreign_keys = ON',
        ];
        if ($this->config->isReplica()) {
            $settings[] = 'PRAGMA query_only = ON';
        }
        $this->getPdo()->exec(implode(';', $settings));
        return $this;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function instantiateQuerySyntax(): SqliteQuerySyntax
    {
        return new SqliteQuerySyntax(
            $this->config,
            $this->identifierDelimiter,
            $this->literalDelimiter,
            $this->dateTimeFormat,
        );
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function instantiateSchemaSyntax(): SqliteSchemaSyntax
    {
        return new SqliteSchemaSyntax(
            $this->config,
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
        if ($ifNotExist && $this->databaseExists()) {
            return;
        }

        $this->getPdo()->exec('CREATE TABLE _setup (id INTEGER PRIMARY KEY AUTOINCREMENT)');
        $this->getPdo()->exec('DROP TABLE _setup');
    }

    /**
     * @inheritDoc
     */
    #[Override]
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
     * @inheritDoc
     */
    #[Override]
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
     * @return bool
     */
    public function isPersistentDatabase(): bool
    {
        $filename = $this->config->filename;
        return $filename !== ':memory:' && $filename !== '';
    }
}
