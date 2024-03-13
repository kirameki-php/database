<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema;

use Kirameki\Database\Statements\Schema\Syntax\SchemaSyntax;

class CreateIndexStatement extends SchemaStatement
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
     * @param SchemaSyntax $syntax
     * @param string $table
     */
    public function __construct(
        SchemaSyntax $syntax,
        public readonly string $table,
    )
    {
        parent::__construct($syntax);
        $this->name = null;
        $this->columns = [];
        $this->unique = null;
        $this->comment = null;
    }
}
