<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Expressions;

use Kirameki\Database\Schema\Syntax\SchemaSyntax;

abstract class DefaultValue
{
    /**
     * @param SchemaSyntax $syntax
     * @return string
     */
    abstract public function toString(SchemaSyntax $syntax): string;
}
