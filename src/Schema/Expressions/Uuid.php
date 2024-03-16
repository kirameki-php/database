<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Expressions;

use Kirameki\Database\Schema\Syntax\SchemaSyntax;

class Uuid extends DefaultValue
{
    /**
     * @param SchemaSyntax $syntax
     * @return string
     */
    public function toString(SchemaSyntax $syntax): string
    {
        return $syntax->formatUuid();
    }
}
