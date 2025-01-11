<?php declare(strict_types=1);

namespace Kirameki\Database\Functions\Syntax;

use Override;

trait MySqlFunctionSyntax
{
    use SharedFunctionSyntax;

    /**
     * @inheritDoc
     */
    #[Override]
    public function formatCurrentTimestamp(?int $size = null): string
    {
        return 'CURRENT_TIMESTAMP(' . ($size) . ')';
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function formatRand(): string
    {
        return 'RAND()';
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function formatUuid(): string
    {
        return 'UUID()';
    }
}
