<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

class AlterDropColumnAction
{
    public string $column;

    public function __construct(string $column)
    {
        $this->column = $column;
    }
}
