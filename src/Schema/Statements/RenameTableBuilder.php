<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

/**
 * @extends SchemaBuilder<RenameTableStatement>
 */
class RenameTableBuilder extends SchemaBuilder
{
    /**
     * @param string $from
     * @param string $to
     */
    public function __construct(
        string $from,
        string $to,
    )
    {
        parent::__construct(new RenameTableStatement($from, $to));
    }
}
