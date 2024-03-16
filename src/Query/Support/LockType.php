<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Support;

enum LockType
{
    case Exclusive;
    case Shared;
}
