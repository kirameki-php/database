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
     * @param list<MigrationPlan> $plans
     */
    public function __construct(
        protected readonly DatabaseManager $db,
        protected readonly EventManager $events,
        protected array $plans = [],
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
     * @param Closure(MigrationPlan): void $callback
     * @return void
     */
    protected function use(string $connection, Closure $callback): void
    {
        $plan = new MigrationPlan($this->db->use($connection));
        $this->plans[] = $plan;
        $callback($plan);
    }

    /**
     * @return list<MigrationPlan>
     */
    public function getPlans(): array
    {
        return new $this->plans;
    }
}
