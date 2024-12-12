<?php declare(strict_types=1);

namespace Kirameki\Database\Functions\Syntax;

use Kirameki\Database\Schema\Syntax\MySqlSchemaSyntax;
use Kirameki\Database\Syntax;
use Override;
use PhpParser\Node\Stmt\Expression;

trait MySqlFunctionSyntax
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function formatCurrentTimestamp(?int $size = null): string
    {
        return 'CURRENT_TIMESTAMP(' . ($size ?? MySqlSchemaSyntax::DEFAULT_TIME_PRECISION) . ')';
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
