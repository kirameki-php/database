<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\Query\Syntax\QuerySyntax;
use Override;

class UpdateStatement extends ConditionsStatement
{
    /**
     * @param QuerySyntax $syntax
     * @param string $table
     * @param array<string, mixed>|null $set
     * @param list<string>|null $returning
     */
    public function __construct(
        QuerySyntax $syntax,
        public readonly string $table,
        public ?array $set = null,
        public ?array $returning = null,
    )
    {
        parent::__construct($syntax);
    }

    /**
     * @inheritDoc
     * @return Executable<self>
     */
    #[Override]
    public function prepare(): Executable
    {
        if ($this->set === null) {
            throw new LogicException('No assignments set for update statement.', [
                'statement' => $this,
            ]);
        }

        return $this->syntax->compileUpdate($this);
    }
}
