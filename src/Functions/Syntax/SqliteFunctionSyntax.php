<?php declare(strict_types=1);

namespace Kirameki\Database\Functions\Syntax;

use Override;
use PhpParser\Node\Stmt\Expression;

trait SqliteFunctionSyntax
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function formatCurrentTimestamp(?int $size = null): string
    {
        return 'DATETIME("now", "localtime")';
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
