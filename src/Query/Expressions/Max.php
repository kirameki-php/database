<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Expressions;

final class Max extends Aggregate
{
    public static string $name = 'MAX';
    public static string $defaultAlias = 'max';
}
