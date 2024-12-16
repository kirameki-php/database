<?php declare(strict_types=1);

namespace Kirameki\Database\Functions\Syntax;

use Kirameki\Database\Expression;
use Override;

trait SqliteFunctionSyntax
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
        return 'DATETIME("now", "localtime")';
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
