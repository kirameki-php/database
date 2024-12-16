<?php declare(strict_types=1);

namespace Kirameki\Database\Functions\Syntax;

use Kirameki\Database\Expression;
use Kirameki\Database\Schema\Syntax\MySqlSchemaSyntax;
use Override;

trait MySqlFunctionSyntax
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function formatCoalesce(array $values): string
    {
        return "COALESCE({$this->asCsv($this->stringifyExpressions($values))})";
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function formatCurrentTimestamp(?int $size = null): string
    {
        return 'CURRENT_TIMESTAMP(' . ($size ?? MySqlSchemaSyntax::DEFAULT_TIME_PRECISION) . ')';
    }

    /**
     * @param string|Expression $target
     * @param string $path
     * @return string
     */
    public function formatJsonExtract(string|Expression $target, string $path): string
    {
        return "{$this->stringifyExpression($target)} -> \"$path\"";
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
