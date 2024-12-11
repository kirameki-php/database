<?php declare(strict_types=1);

namespace Kirameki\Database\Expressions;

use Kirameki\Database\Syntax;

/**
 * @template TSyntax of Syntax = Syntax
 */
interface Expression
{
    /**
     * @param TSyntax $syntax
     * @return string
     */
    public function toValue(Syntax $syntax): string;
}
