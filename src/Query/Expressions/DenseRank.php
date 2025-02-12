<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Expressions;

final class DenseRank extends RankingFunction
{
    public static string $name = 'DENSE_RANK';
    public static string $defaultAlias = 'rank';
}
