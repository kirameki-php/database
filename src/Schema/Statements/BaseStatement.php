<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

abstract class BaseStatement
{
    /**
     * @var string
     */
    public string $table;

    /**
     * @param string $table
     */
    public function __construct(string $table)
    {
        $this->table = $table;
    }
}
