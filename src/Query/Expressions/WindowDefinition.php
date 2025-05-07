<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Expressions;

use Kirameki\Database\Query\Statements\Ordering;

class WindowDefinition
{
    /**
     * @var list<string>|null
     */
    public ?array $partitionBy = null;

    /**
     * @var array<string, Ordering>|null
     */
    public ?array $orderBy = null;
}
