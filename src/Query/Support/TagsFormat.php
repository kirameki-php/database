<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Support;

enum TagsFormat
{
    case Log;
    case OpenTelemetry;
}
