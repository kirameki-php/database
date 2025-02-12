<?php

declare(strict_types=1);

namespace Kirameki\Database\Query\Expressions;

use Kirameki\Database\Functions\Coalesce;
use Kirameki\Database\Functions\CurrentTimestamp;
use Kirameki\Database\Functions\JsonExtract;
use Kirameki\Database\Functions\Uuid;

/**
 * @param string ...$columns
 * @return Coalesce
 */
function coalesce(string ...$columns): Coalesce
{
    return Coalesce::columns(...$columns);
}

/**
 * @param int|null $size
 * @return CurrentTimestamp
 */
function current_timestamp(?int $size = null): CurrentTimestamp
{
    return new CurrentTimestamp($size);
}

function json_extract(string $column, string $path, ?string $as = null): JsonExtract
{
    return JsonExtract::column($column, $path, $as);
}

function uuid(): Uuid
{
    return new Uuid();
}
