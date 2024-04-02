<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

use Kirameki\Database\Exceptions\DatabaseNotFoundException;
use Kirameki\Database\Query\Syntax\SqliteQuerySyntax;
use Kirameki\Database\Schema\Statements\CreateTableBuilder;
use Kirameki\Database\Schema\Statements\DropTableBuilder;
use Kirameki\Database\Schema\Syntax\SqliteSchemaSyntax;
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
        $options += [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        ];

        return new PDO($dsn, null, null, $options);
    }

    /**
     * @inheritDoc
     */
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
    protected function instantiateSchemaSyntax(): SqliteSchemaSyntax
    {
        return new SqliteSchemaSyntax(
            $this->config,
            $this->identifierDelimiter,
            $this->literalDelimiter,
        );
    }

    /**
     * @param bool $ifNotExist
     * @return void
     */
    public function createDatabase(bool $ifNotExist = true): void
    {
        if ($ifNotExist && $this->databaseExists()) {
            return;
        }

        $dummyTableName = '_';
        $schemaSyntax = $this->getSchemaSyntax();
        $createTable = new CreateTableBuilder($schemaSyntax, $dummyTableName);
        $createTable->int('id')->primaryKey()->autoIncrement();
        $this->runSchema($createTable->getStatement());
        $dropTable = new DropTableBuilder($schemaSyntax, $dummyTableName);
        $this->runSchema($dropTable->getStatement());
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
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
     * @return bool
     */
    public function isPersistentDatabase(): bool
    {
        $filename = $this->config->filename;
        return $filename !== ':memory:' && $filename !== '';
    }
}
