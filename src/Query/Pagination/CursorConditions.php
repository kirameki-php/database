<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Pagination;

use Kirameki\Core\Json;
use Kirameki\Database\Query\Statements\QueryBuilder;
use Kirameki\Database\Query\Statements\SelectBuilder;
use Kirameki\Database\Query\Support\Ordering;
use Kirameki\Database\Query\Support\SortOrder;
use function compact;
use function str_replace;

class CursorConditions
{
    /**
     * @var list<array{column: string, order: Ordering, value: mixed}>
     */
    public array $conditions = [];

    /**
     * @param int $page
     */
    public function __construct(
        public readonly int $page,
    )
    {
    }

    /**
     * @param string $column
     * @param Ordering $order
     * @param mixed $value
     * @return void
     */
    public function add(string $column, Ordering $order, mixed $value): void
    {
        $this->conditions[] = compact('column', 'order', 'value');
    }

    /**
     * @return string
     */
    public function urlEncode(): string
    {
        return str_replace(['+', '/'], ['-', '_'], base64_encode(Json::encode($this->conditions)));
    }

    public function apply(SelectBuilder $builder): void
    {
        foreach ($this->conditions as $condition) {
            $column = $condition['column'];
            $ordering = $condition['order'];
            $value = $condition['value'];
            $builder->where($column, $ordering->sort === SortOrder::Ascending ? '>' : '<', $value);
            $builder->orderBy($column, $ordering->sort);
        }
    }
}
