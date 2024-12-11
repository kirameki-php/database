<?php declare(strict_types=1);

namespace Kirameki\Database\Expressions;

use Kirameki\Database\Schema\Syntax\SchemaSyntax;
use Kirameki\Database\Syntax;
use Override;

/**
 * @implements Expression<SchemaSyntax>
 */
class Uuid implements Expression
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function toValue(Syntax $syntax): string
    {
        return $syntax->formatUuid();
    }
}
