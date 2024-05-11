<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Support;

enum LockType: string
{
    case Exclusive = 'FOR UPDATE';
    case Shared = 'FOR SHARE';
}
