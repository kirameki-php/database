<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema;

use Kirameki\Database\Statements\Schema\Syntax\SchemaSyntax;
use RuntimeException;

class DropIndexStatement extends SchemaStatement
{
    /**
     * @var string|null
     */
    public ?string $name;

    /**
     * @var string[]
     */
    public array $columns = [];

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
    }

    /**
     * @return void
     */
    public function preprocess(): void
    {
        $name = $this->name;
        $columns = $this->columns;

        if($name === null && empty($columns)) {
            throw new RuntimeException('Name or column(s) are required to drop an index.');
        }
    }

    /**
     * @return list<string>
     */
    public function prepare(): array
    {
        $this->preprocess();
        return [
            $this->syntax->formatDropIndexStatement($this)
        ];
    }
}
