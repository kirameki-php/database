<?php declare(strict_types=1);

namespace Kirameki\Database\Functions\Syntax;

use Kirameki\Database\Expression;
use Override;

trait SharedFunctionSyntax
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
    public function formatJsonExtract(string|Expression $target, string $path, ?string $as): string
    {
        return $this->concat([
            $this->stringifyExpression($target),
            '->',
            $this->asLiteral($path),
            $as !== null ? "AS {$this->asColumn($as)}" : null,
        ]);
    }
}
