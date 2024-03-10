<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Query\Support;

enum LockOption
{
    case Nowait;
    case SkipLocked;
}
