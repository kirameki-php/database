<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Expressions;

abstract class RankingFunction extends QueryFunction
{
    public function __construct(?string $as = null)
    {
        parent::__construct(null, $as);
    }
}
