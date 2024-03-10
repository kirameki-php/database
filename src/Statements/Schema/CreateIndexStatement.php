<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema;

class CreateIndexStatement extends Statement
{
    /**
     * @var string|null
     */
    public ?string $name;

    /**
     * @var array<array-key, string>
     */
    public array $columns;

    /**
     * @var bool
     */
    public ?bool $unique;

    /**
     * @var string|null
     */
    public ?string $comment;

    /**
     * @param string $table
     */
    public function __construct(string $table)
    {
        parent::__construct($table);
        $this->name = null;
        $this->columns = [];
        $this->unique = null;
        $this->comment = null;
    }
}
