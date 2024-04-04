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

    public function __construct(
        SchemaSyntax $syntax,
        public readonly string $table,
    )
    {
        parent::__construct($syntax);
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
    public function toCommands(): array
    {
        return $this->syntax->compileAlterTable($this);
    }
}
