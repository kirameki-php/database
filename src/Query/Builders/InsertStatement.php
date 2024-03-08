<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Builders;

use function array_keys;

class InsertStatement extends Statement
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
     * @param string $table
     */
    public function __construct(
        public readonly string $table,
    )
    {
    }

    /**
     * @return array<string>
     */
    public function columns(): array
    {
        if ($this->cachedColumns === null) {
            $columnsAssoc = [];
            foreach ($this->dataset as $data) {
                foreach($data as $name => $value) {
                    if ($value !== null) {
                        $columnsAssoc[$name] = null;
                    }
                }
            }
            $this->cachedColumns = array_keys($columnsAssoc);
        }
        return $this->cachedColumns;
    }
}
