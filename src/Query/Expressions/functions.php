<?php

declare(strict_types=1);

namespace Kirameki\Database\Query\Expressions;

function row_number(?string $as = null): RowNumber
{
    return new RowNumber($as);
}

function rank(?string $as = null): Rank
{
    return new Rank($as);
}

function dense_rank(?string $as = null): DenseRank
{
    return new DenseRank($as);
}

function count(string $column = '*', ?string $alias = null): Count
{
    return new Count($column, $alias);
}
