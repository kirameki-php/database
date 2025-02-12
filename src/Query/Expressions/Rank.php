<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Expressions;

final class Rank extends RankingFunction
{
    public static string $name = 'RANK';
    public static string $defaultAlias = 'rank';
}
