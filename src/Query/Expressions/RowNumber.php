<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Expressions;

final class RowNumber extends RankingFunction
{
    public static string $name = 'ROW_NUMBER';
    public static string $defaultAlias = 'row';
}
