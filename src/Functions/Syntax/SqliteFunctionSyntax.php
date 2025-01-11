<?php declare(strict_types=1);

namespace Kirameki\Database\Functions\Syntax;

use Override;

trait SqliteFunctionSyntax
{
    use SharedFunctionSyntax;

    /**
     * @inheritDoc
     */
    #[Override]
    public function formatCurrentTimestamp(?int $size = null): string
    {
        return $size !== null
            ? 'STRFTIME("%Y-%m-%d %H:%M:%f", DATETIME("now", "localtime"))'
            : 'DATETIME("now", "localtime")';
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function formatRand(): string
    {
        return 'RANDOM()';
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
