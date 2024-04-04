<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

use Kirameki\Database\Schema\Syntax\SchemaSyntax;
use Override;
use RuntimeException;

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

    /**
     * @inheritDoc
     */
    #[Override]
    public function toCommands(): array
    {
        $this->preprocess();
        return $this->syntax->compileCreateIndex($this);
    }

    /**
     * @return void
     */
    public function preprocess(): void
    {
        $columns = $this->columns;

        if (empty($columns)) {
            throw new RuntimeException('At least 1 column needs to be defined to create an index.');
        }
    }
}
