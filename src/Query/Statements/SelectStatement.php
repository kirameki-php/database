<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Expressions\Expression;
use Kirameki\Database\Query\Support\LockOption;
use Kirameki\Database\Query\Support\LockType;
use Kirameki\Database\Query\Support\QueryTags;
use Kirameki\Database\Query\Syntax\QuerySyntax;
use Override;

class SelectStatement extends ConditionsStatement
{
    /**
     * @var bool|null
     */
    public ?bool $distinct = null;

    /**
     * @var LockType|null
     */
    public LockType|null $lockType = null;

    /**
     * @var LockOption|null
     */
    public LockOption|null $lockOption = null;

    /**
     * @var string|null
     */
    public ?string $forceIndex = null;

    /**
     * @param QuerySyntax $syntax
     * @param QueryTags|null $tags
     * @param list<string|Expression> $tables
     * @param list<string|Expression>|null $columns
     * @param list<JoinDefinition>|null $joins
     * @param list<string>|null $groupBy
     * @param list<ConditionDefinition>|null $having
     * @param int|null $offset
     */
    public function __construct(
        QuerySyntax $syntax,
        ?QueryTags $tags = null,
        public ?array $tables = null,
        public ?array $columns = null,
        public ?array $joins = null,
        public ?array $groupBy = null,
        public ?array $having = null,
        public ?int $offset = null,
    )
    {
        parent::__construct($syntax, $tags);
    }

    /**
     * @inheritDoc
     * @return Executable<self>
     */
    #[Override]
    public function prepare(): Executable
    {
        return $this->syntax->compileSelect($this);
    }
}
