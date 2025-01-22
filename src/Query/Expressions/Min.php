<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Expressions;

final class Min extends Aggregate
{
    public static string $function = 'MIN';
    public static string $defaultAlias = 'min';
}
