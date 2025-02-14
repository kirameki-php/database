<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

use Kirameki\Core\Exceptions\NotSupportedException;
use Kirameki\Database\Config\SqliteConfig;
use Kirameki\Database\Exceptions\DatabaseExistsException;
use Kirameki\Database\Exceptions\DatabaseNotFoundException;
use Kirameki\Database\Exceptions\DropProtectionException;
use Kirameki\Database\Query\Syntax\SqliteQuerySyntax;
use Kirameki\Database\Schema\Statements\RawStatement;
use Kirameki\Database\Schema\Syntax\SqliteSchemaSyntax;
use Kirameki\Database\Transaction\TransactionOptions;
use Override;
use PDO;
use PDOException;
use function file_exists;
use function glob;
use function implode;
use function iterator_to_array;
use function unlink;

/**
 * @extends PdoAdapter<SqliteConfig>
 */
class SqliteAdapter extends PdoAdapter
{
    /**
     * p is changed to P to prevent 00:00 from being converted to 'Z' which will cause problems
     * when sorting since SQLite will treat datetime as string.
     *
     * @inheritdoc
     */
    protected string $dateTimeFormat = 'Y-m-d\TH:i:s.uP';

    /**
     * @inheritDoc
     */
    #[Override]
    public function createPdo(): PDO
    {
        $config = $this->connectionConfig;

        $dsn = "sqlite:{$config->filename}";
        $options = iterator_to_array($config->options ?? []);
        $options += [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_TIMEOUT => $config->busyTimeoutSeconds,
        ];

        if ($config->isReadOnly()) {
            $options[PDO::SQLITE_ATTR_OPEN_FLAGS] = PDO::SQLITE_OPEN_READONLY;
        }

        return new PDO($dsn, null, null, $options);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function connect(): static
    {
        parent::connect();

        $pragmas = $this->connectionConfig->pragmas ?? [];
        $pragmas += [
            // As of SQLite version 3.6.19, the default setting for foreign key enforcement is OFF
            // https://sqlite.org/pragma.html#pragma_foreign_keys
            'foreign_keys' => 'ON',
            // WAL is significantly faster in most scenarios.
            // https://www.sqlite.org/wal.html
            'journal_mode' => 'WAL',
            // The synchronous=NORMAL setting is a good choice for most applications running in WAL mode.
            // https://www.sqlite.org/pragma.html#pragma_synchronous
            'synchronous' => 'NORMAL',
            // The query_only pragma prevents data changes on database files when enabled.
            // https://sqlite.org/pragma.html#pragma_query_only
            'query_only' => $this->connectionConfig->isReadOnly() ? 'ON' : 'OFF',
        ];

        $statements = [];
        foreach ($pragmas as $name => $value) {
            $statements[] = "PRAGMA {$name}={$value}";
        }

        try {
            $this->executeRawStatement(implode(';', $statements));
        } catch (PDOException $e) {
            $this->throwConnectionException($e);
        }

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
        if ($this->databaseExists()) {
            if ($ifNotExist) {
                return;
            }
            throw new DatabaseExistsException($this->connectionConfig->filename, [
                'adapter' => $this,
            ]);
        }

        $statements = [
            new RawStatement('CREATE TABLE _setup (id INTEGER PRIMARY KEY AUTOINCREMENT)'),
            new RawStatement('DROP TABLE _setup'),
        ];
        foreach ($statements as $statement) {
            $this->runSchema($statement);
        }
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function dropDatabase(bool $ifExist = true): void
    {
        if ($this->databaseConfig->dropProtection) {
            $database = $this->connectionConfig->filename;
            throw new DropProtectionException("Dropping database '{$database}' is prohibited.", [
                'adapter' => $this,
            ]);
        }

        if ($this->databaseExists()) {
            if ($this->isPersistentDatabase()) {
                // remove all related files ({name}.db / {name}.db-shm / {name}.db-wal)
                $files = glob($this->connectionConfig->filename . '*') ?: [];
                foreach ($files as $file) {
                    unlink($file);
                }
            }
        } elseif (!$ifExist) {
            throw new DatabaseNotFoundException($this->connectionConfig->filename, [
                'adapter' => $this,
            ]);
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

        // In-memory or temporary databases only exist when connected.
        if (!$this->isPersistentDatabase()) {
            return false;
        }

        return file_exists($this->connectionConfig->filename);
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
    public function beginTransaction(?TransactionOptions $options = null): void
    {
        $level = $options?->isolationLevel;

        if ($level !== null) {
            throw new NotSupportedException('Transaction Isolation level cannot be changed in SQLite.', [
                'adapter' => $this,
                'level' => $level,
            ]);
        }
        $this->tryTransactionCall($this->getPdo()->beginTransaction(...));
    }
}
