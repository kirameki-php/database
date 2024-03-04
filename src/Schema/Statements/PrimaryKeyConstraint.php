<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

class PrimaryKeyConstraint
{
    /**
     * @var list<string>
     */
    public array $columns = [];
}