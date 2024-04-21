<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

use Kirameki\Database\Schema\Support\ReferenceOption;

class ForeignKeyConstraint
{
    /**
     * @param list<string> $foreignKeyColumns
     * @param string $referenceTable
     * @param list<string> $referenceColumns
     * @param string|null $name
     * @param ReferenceOption|null $onDelete
     * @param ReferenceOption|null $onUpdate
     */
    public function __construct(
        public readonly array $foreignKeyColumns,
        public readonly string $referenceTable,
        public readonly array $referenceColumns,
        public ?string $name = null,
        public ?ReferenceOption $onDelete = null,
        public ?ReferenceOption $onUpdate = null,
    )
    {
    }
}
