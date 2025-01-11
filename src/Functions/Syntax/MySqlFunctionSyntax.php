<?php declare(strict_types=1);

namespace Kirameki\Database\Functions\Syntax;

use Kirameki\Database\Expression;
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
        return 'CURRENT_TIMESTAMP(' . ($size) . ')';
    }

    /**
     * @inheritDoc
     */
    public function formatJsonExtract(string|Expression $target, string $path, ?string $as): string
    {
        return $this->concat([
            $this->stringifyExpression($target),
            '->',
            $this->asLiteral($path),
            $as !== null ? "AS {$this->asColumn($as)}" : null,
        ]);
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
