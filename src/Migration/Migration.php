<?php declare(strict_types=1);

namespace Kirameki\Database\Migration;

use Closure;
use Kirameki\Database\DatabaseManager;
use Kirameki\Event\EventManager;

abstract class Migration
{
    /**
     * @param DatabaseManager $db
     * @param EventManager $events
     * @param list<MigrationBuilder> $builders
     */
    public function __construct(
        protected readonly DatabaseManager $db,
        protected readonly EventManager $events,
        protected array $builders = [],
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
     * @param Closure $callback
     * @return void
     */
    protected function on(string $connection, Closure $callback): void
    {
        $builder = new MigrationBuilder($this->db->use($connection), $this->events);
        $this->builders[] = $builder;
        $callback($builder);
    }

    /**
     * @return list<MigrationBuilder>
     */
    public function getBuilders(): array
    {
        return new $this->builders;
    }
}
