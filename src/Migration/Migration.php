<?php declare(strict_types=1);

namespace Kirameki\Database\Migration;

use DateTime;
use Kirameki\Collections\Utils\Arr;
use Kirameki\Database\Connection;
use Kirameki\Database\DatabaseManager;
use Kirameki\Database\Schema\Builders\AlterTableBuilder;
use Kirameki\Database\Schema\Builders\CreateIndexBuilder;
use Kirameki\Database\Schema\Builders\CreateTableBuilder;
use Kirameki\Database\Schema\Builders\DropIndexBuilder;
use Kirameki\Database\Schema\Builders\DropTableBuilder;
use Kirameki\Database\Schema\Builders\RenameTableBuilder;
use Kirameki\Database\Schema\Builders\StatementBuilder;
use Kirameki\Database\Schema\Statements\CreateIndexStatement;
use Kirameki\Database\Schema\Statements\DropIndexStatement;

abstract class Migration
{
    /**
     * @var string|null
     */
    protected ?string $time;

    /**
     * @var StatementBuilder[]
     */
    protected array $builders;

    /**
     * @var Connection
     */
    protected Connection $using;

    /**
     * @param DatabaseManager $db
     * @param string|null $time
     */
    public function __construct(
        protected DatabaseManager $db,
        ?string $time = null,
    )
    {
        $this->time = $time;
        $this->builders = [];
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
     * @return DateTime
     */
    public function getCreatedTime(): DateTime
    {
        return DateTime::createFromFormat('YmdHis', $this->time);
    }

    /**
     * @return StatementBuilder[]
     */
    public function getBuilders(): array
    {
        return $this->builders;
    }

    /**
     * @return string[]
     */
    public function toStatements(): array
    {
        return Arr::flatMap($this->builders, fn(StatementBuilder $b) => $b->build());
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
        $statement = new CreateIndexStatement($table);
        return $this->builders[] = new CreateIndexBuilder($this->using, $statement);
    }

    /**
     * @param string $table
     * @return DropIndexBuilder
     */
    public function dropIndex(string $table): DropIndexBuilder
    {
        $statement = new DropIndexStatement($table);
        return $this->builders[] = new DropIndexBuilder($this->using, $statement);
    }
}
