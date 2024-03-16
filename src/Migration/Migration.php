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
     */
    public function __construct(
        protected readonly DatabaseManager $db,
        protected readonly EventManager $events,
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
        $callback($builder);
        $builder->apply();
    }
}
