<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements\ForeignKey;

class ForeignKeyConstraint
{
    /**
     * @param list<string> $columns
     * @param string $referencedTable
     * @param list<string> $referencedColumns
     * @param string|null $name
     * @param ReferenceOption|null $onDelete
     * @param ReferenceOption|null $onUpdate
     */
    public function __construct(
        public readonly array $columns,
        public readonly string $referencedTable,
        public readonly array $referencedColumns,
        public ?string $name = null,
        public ?ReferenceOption $onDelete = null,
        public ?ReferenceOption $onUpdate = null,
    )
    {
    }
}
