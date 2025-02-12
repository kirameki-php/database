<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Expressions;

final class Avg extends Aggregate
{
    public static string $name = 'AVG';
    public static string $defaultAlias = 'avg';
}
