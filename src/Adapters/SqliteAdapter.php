<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

use Kirameki\Core\Exceptions\NotSupportedException;
use Kirameki\Database\Config\SqliteConfig;
use Kirameki\Database\Exceptions\DatabaseNotFoundException;
use Kirameki\Database\Query\Syntax\SqliteQuerySyntax;
use Kirameki\Database\Schema\Syntax\SqliteSchemaSyntax;
use Kirameki\Database\Transaction\Support\IsolationLevel;
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
        $config = $this->getConnectionConfig();

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
            // As of SQLite version 3.6.19, the default setting for foreign key enforcement is OFF
            // https://sqlite.org/pragma.html#pragma_foreign_keys
            'PRAGMA foreign_keys = ON',
            // WAL is significantly faster in most scenarios.
            // https://www.sqlite.org/wal.html
            'PRAGMA journal_mode = WAL',
        ];
        if ($this->connectionConfig->isReplica()) {
            // The query_only pragma prevents data changes on database files when enabled.
            // https://sqlite.org/pragma.html#pragma_query_only
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
    protected function instantiateSchemaSyntax(): SqliteSchemaSyntax
    {
        return new SqliteSchemaSyntax(
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
                unlink($this->connectionConfig->filename);
            }
        } elseif (!$ifExist) {
            throw new DatabaseNotFoundException($this->connectionConfig->filename, $this->connectionConfig);
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

        $filename = $this->connectionConfig->filename;

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
        $filename = $this->connectionConfig->filename;
        return $filename !== ':memory:' && $filename !== '';
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function beginTransaction(?IsolationLevel $level = null): void
    {
        if ($level !== null) {
            throw new NotSupportedException('Transaction Isolation level changes are not supported in SQLite.');
        }
        $this->getPdo()->beginTransaction();
    }
}
