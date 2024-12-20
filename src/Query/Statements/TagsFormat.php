<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

enum TagsFormat
{
    case Log;
    case OpenTelemetry;
}
