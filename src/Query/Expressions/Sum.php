<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Expressions;

final class Sum extends Aggregate
{
    public static string $function = 'SUM';
    public static string $defaultAlias = 'sum';
}
