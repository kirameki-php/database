<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements\Index;

use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\Schema\Statements\SchemaStatement;
use Kirameki\Database\Schema\Syntax\SchemaSyntax;
use Override;

class DropIndexStatement extends SchemaStatement
{
    /**
     * @param string $table
     * @param array<int, string> $columns
     * @param string|null $name
     */
    public function __construct(
        public readonly string $table,
        public array $columns,
        public ?string $name,
    )
    {
    }

    /**
     * @return void
     */
    public function preprocess(): void
    {
        $name = $this->name;
        $columns = $this->columns;

        if ($name === null && empty($columns)) {
            throw new LogicException('Name or column(s) are required to drop an index.', [
                'statement' => $this,
            ]);
        }
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function toExecutable(SchemaSyntax $syntax): array
    {
        $this->preprocess();
        return $syntax->compileDropIndex($this);
    }
}
