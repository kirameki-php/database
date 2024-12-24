<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

enum LockType: string
{
    case Exclusive = 'FOR UPDATE';
    case Shared = 'FOR SHARE';
}
