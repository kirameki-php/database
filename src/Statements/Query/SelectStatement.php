<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Query;

use Kirameki\Database\Statements\Expression;
use Kirameki\Database\Statements\Query\Support\LockOption;
use Kirameki\Database\Statements\Query\Support\LockType;

class SelectStatement extends ConditionsStatement
{
    /**
     * @var array<string|Expression>
     */
    public array $tables = [];

    /**
     * @var array<string|Expression>|null
     */
    public ?array $columns = null;

    /**
     * @var array<JoinDefinition>|null
     */
    public ?array $joins = null;

    /**
     * @var array<string>|null
     */
    public ?array $groupBy = null;

    /**
     * @var array<ConditionDefinition>
     */
    public ?array $having = null;

    /**
     * @var int|null
     */
    public ?int $offset = null;

    /**
     * @var bool
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
     * @inheritDoc
     */
    public function prepare(): string
    {
        return $this->syntax->compileSelectStatement($this);
    }

    /**
     * @inheritDoc
     */
    public function getParameters(): array
    {
        return $this->syntax->prepareParametersForSelect($this);
    }
}
