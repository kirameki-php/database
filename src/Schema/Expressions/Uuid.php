<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Expressions;

use Kirameki\Database\Schema\Syntax\SchemaSyntax;
use Override;

class Uuid extends DefaultValue
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function toString(SchemaSyntax $syntax): string
    {
        return $syntax->formatUuid();
    }
}
