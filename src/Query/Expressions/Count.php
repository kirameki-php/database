<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Expressions;

final class Count extends Aggregate
{
    public static string $name = 'COUNT';
    public static string $defaultAlias = 'count';
}
