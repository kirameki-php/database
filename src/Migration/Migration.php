<?php declare(strict_types=1);

namespace Kirameki\Database\Migration;

use Kirameki\Collections\Utils\Arr;
use Kirameki\Database\Connection;
use Kirameki\Database\DatabaseManager;
use Kirameki\Database\Statements\OldStatementBuilder;
use Kirameki\Database\Statements\Schema\AlterTableBuilder;
use Kirameki\Database\Statements\Schema\CreateIndexBuilder;
use Kirameki\Database\Statements\Schema\CreateTableBuilder;
use Kirameki\Database\Statements\Schema\DropIndexBuilder;
use Kirameki\Database\Statements\Schema\DropTableBuilder;
use Kirameki\Database\Statements\Schema\RenameTableBuilder;

abstract class Migration
{
    /**
     * @var list<OldStatementBuilder>
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
    public function using(string $connection): static
    {
        $this->using = $this->db->using($connection);
        return $this;
    }

    /**
     * @return list<OldStatementBuilder>
     */
    public function getBuilders(): array
    {
        return $this->builders;
    }

    /**
     * @return list<string>
     */
    public function toStatements(): array
    {
        return Arr::flatMap($this->builders, fn(OldStatementBuilder $b) => $b->build());
    }

    /**
     * @return void
     */
    public function apply(): void
    {
        foreach ($this->toStatements() as $statement) {
            $this->using->applySchema($statement);
        }
    }

    /**
     * @param string $table
     * @return CreateTableBuilder
     */
    public function createTable(string $table): CreateTableBuilder
    {
        return $this->builders[] = new CreateTableBuilder($this->using, $table);
    }

    /**
     * @param string $table
     * @return DropTableBuilder
     */
    public function dropTable(string $table): DropTableBuilder
    {
        return $this->builders[] = new DropTableBuilder($this->using, $table);
    }

    /**
     * @param string $table
     * @return AlterTableBuilder
     */
    public function alterTable(string $table): AlterTableBuilder
    {
        return $this->builders[] = new AlterTableBuilder($this->using, $table);
    }

    /**
     * @param string $from
     * @param string $to
     * @return RenameTableBuilder
     */
    public function renameTable(string $from, string $to): RenameTableBuilder
    {
        return $this->builders[] = new RenameTableBuilder($this->using, $from, $to);
    }

    /**
     * @param string $table
     * @return CreateIndexBuilder
     */
    public function createIndex(string $table): CreateIndexBuilder
    {
        return $this->builders[] = new CreateIndexBuilder($this->using, $table);
    }

    /**
     * @param string $table
     * @return DropIndexBuilder
     */
    public function dropIndex(string $table): DropIndexBuilder
    {
        return $this->builders[] = new DropIndexBuilder($this->using, $table);
    }
}
