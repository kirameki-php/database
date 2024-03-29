<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Syntax\QuerySyntax;
use function array_keys;

class InsertStatement extends QueryStatement
{
    /**
     * @var array<int, array<string, mixed>>
     */
    public array $dataset;

    /**
     * @var array<string>|null
     */
    public ?array $returning = null;

    /**
     * @var array<string>|null
     */
    protected ?array $cachedColumns = null;

    /**
     * @param QuerySyntax $syntax
     * @param string $table
     */
    public function __construct(
        QuerySyntax $syntax,
        public readonly string $table,
    )
    {
        parent::__construct($syntax);
    }

    /**
     * @return array<string>
     */
    public function columns(): array
    {
        if ($this->cachedColumns === null) {
            $columnsMap = [];
            foreach ($this->dataset as $data) {
                foreach ($data as $name => $value) {
                    if ($value !== null) {
                        $columnsMap[$name] = null;
                    }
                }
            }
            $this->cachedColumns = array_keys($columnsMap);
        }
        return $this->cachedColumns;
    }

    /**
     * @return string
     */
    public function prepare(): string
    {
        return $this->syntax->formatInsertStatement($this);
    }

    /**
     * @return array<mixed>
     */
    public function getParameters(): array
    {
        return $this->syntax->prepareParametersForInsert($this);
    }
}
