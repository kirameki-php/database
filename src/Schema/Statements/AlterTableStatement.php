<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

use Kirameki\Collections\Utils\Arr;
use Kirameki\Core\Exceptions\InvalidTypeException;
use Kirameki\Database\Schema\Syntax\SchemaSyntax;
use Override;
use function array_map;

class AlterTableStatement extends SchemaStatement
{
    /**
     * @var list<mixed>
     */
    public array $actions;

    /**
     * @param string $table
     */
    public function __construct(
        public readonly string $table,
    )
    {
    }

    /**
     * @param mixed $action
     */
    public function addAction(mixed $action): void
    {
        $this->actions[] = $action;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function toExecutable(SchemaSyntax $syntax): array
    {
        return $syntax->compileAlterTable($this);
    }
}
