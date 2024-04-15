<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

use Kirameki\Database\Schema\Syntax\SchemaSyntax;
use Override;
use RuntimeException;

class DropIndexStatement extends SchemaStatement
{
    /**
     * @param SchemaSyntax $syntax
     * @param string $table
     * @param string|null $name
     * @param list<string> $columns
     */
    public function __construct(
        SchemaSyntax $syntax,
        public readonly string $table,
        public ?string $name = null,
        public array $columns = [],
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

        if ($name === null && empty($columns)) {
            throw new RuntimeException('Name or column(s) are required to drop an index.');
        }
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function toCommands(): array
    {
        $this->preprocess();
        return $this->syntax->compileDropIndex($this);
    }
}
