<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema;

class PrimaryKeyConstraint
{
    /**
     * @var array<string, string>
     */
    public array $columns = [];
}