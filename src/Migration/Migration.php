<?php declare(strict_types=1);

namespace Kirameki\Database\Migration;

use Kirameki\Collections\Utils\Arr;
use Kirameki\Database\Connection;
use Kirameki\Database\DatabaseManager;
use Kirameki\Database\Statements\Schema\AlterTableBuilder;
use Kirameki\Database\Statements\Schema\CreateIndexBuilder;
use Kirameki\Database\Statements\Schema\CreateTableBuilder;
use Kirameki\Database\Statements\Schema\DropIndexBuilder;
use Kirameki\Database\Statements\Schema\DropTableBuilder;
use Kirameki\Database\Statements\Schema\RenameTableBuilder;
use Kirameki\Database\Statements\Schema\SchemaBuilder;
use Kirameki\Database\Statements\Schema\SchemaStatement;
use Kirameki\Database\Statements\Schema\Syntax\SchemaSyntax;

abstract class Migration
{
    /**
     * @var list<SchemaBuilder<covariant SchemaStatement>>
     */
    protected array $builders = [];

    /**
     * @param DatabaseManager $db
     * @param Connection $using
     */
    public function __construct(
        protected DatabaseManager $db,
        protected Connection $using,
    )
    {
    }

    /**
     * @return void
     */
    abstract public function up(): void;

    /**
     * @return void
     */
    abstract public function down(): void;

    /**
     * @param string $connection
     * @return $this
     */
    public function use(string $connection): static
    {
        $this->using = $this->db->use($connection);
        return $this;
    }

    /**
     * @return list<SchemaStatement>
     */
    public function toStatements(): array
    {
        return Arr::map($this->builders, static fn(SchemaBuilder $b) => $b->getStatement());
    }

    /**
     * @return void
     */
    public function apply(): void
    {
        foreach ($this->toStatements() as $statement) {
            $this->using->adapter->runSchema($statement);
        }
    }

    /**
     * @param string $table
     * @return CreateTableBuilder
     */
    public function createTable(string $table): CreateTableBuilder
    {
        return $this->builders[] = new CreateTableBuilder($this->getSyntax(), $table);
    }

    /**
     * @param string $table
     * @return DropTableBuilder
     */
    public function dropTable(string $table): DropTableBuilder
    {
        return $this->builders[] = new DropTableBuilder($this->getSyntax(), $table);
    }

    /**
     * @param string $table
     * @return AlterTableBuilder
     */
    public function alterTable(string $table): AlterTableBuilder
    {
        return $this->builders[] = new AlterTableBuilder($this->getSyntax(), $table);
    }

    /**
     * @param string $from
     * @param string $to
     * @return RenameTableBuilder
     */
    public function renameTable(string $from, string $to): RenameTableBuilder
    {
        return $this->builders[] = new RenameTableBuilder($this->getSyntax(), $from, $to);
    }

    /**
     * @param string $table
     * @return CreateIndexBuilder
     */
    public function createIndex(string $table): CreateIndexBuilder
    {
        return $this->builders[] = new CreateIndexBuilder($this->getSyntax(), $table);
    }

    /**
     * @param string $table
     * @return DropIndexBuilder
     */
    public function dropIndex(string $table): DropIndexBuilder
    {
        return $this->builders[] = new DropIndexBuilder($this->getSyntax(), $table);
    }

    /**
     * @return SchemaSyntax
     */
    protected function getSyntax(): SchemaSyntax
    {
        return $this->using->adapter->getSchemaSyntax();
    }
}
