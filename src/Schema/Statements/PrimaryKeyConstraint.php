<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

class PrimaryKeyConstraint
{
    /**
     * @var string[]
     */
    public array $columns;

    /**
     */
    public function __construct()
    {
        $this->columns = [];
    }
}