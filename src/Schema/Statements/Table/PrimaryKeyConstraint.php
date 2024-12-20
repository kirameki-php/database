<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements\Table;

use Kirameki\Database\Query\Statements\SortOrder;

class PrimaryKeyConstraint
{
    /**
     * @var array<string, SortOrder>
     */
    public array $columns = [];
}
