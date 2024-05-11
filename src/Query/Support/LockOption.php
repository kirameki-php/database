<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Support;

enum LockOption: string
{
    case Nowait = 'NOWAIT';
    case SkipLocked = 'SKIP LOCKED';
}
