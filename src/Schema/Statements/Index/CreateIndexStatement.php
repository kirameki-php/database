<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements\Index;

use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\Query\Statements\SortOrder;
use Kirameki\Database\Schema\Statements\SchemaStatement;
use Kirameki\Database\Schema\Syntax\SchemaSyntax;
use Override;

class CreateIndexStatement extends SchemaStatement
{
    /**
     * @param string $table
     * @param string|null $name
     * @param array<array-key, SortOrder> $columns
     * @param bool|null $unique
     */
    public function __construct(
        public readonly string $table,
        public ?string $name = null,
        public array $columns = [],
        public ?bool $unique = null,
    )
    {
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function toExecutable(SchemaSyntax $syntax): array
    {
        $this->preprocess();
        return $syntax->compileCreateIndex($this);
    }

    /**
     * @return void
     */
    public function preprocess(): void
    {
        $columns = $this->columns;

        if (empty($columns)) {
            throw new LogicException('At least 1 column needs to be defined to create an index.', [
                'statement' => $this,
            ]);
        }
    }
}
