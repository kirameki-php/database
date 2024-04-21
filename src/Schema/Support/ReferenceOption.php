<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Support;

enum ReferenceOption: string
{
    case SetNull = 'SET NULL';
    case SetDefault = 'SET DEFAULT';
    case Cascade = 'CASCADE';
    case Restrict = 'RESTRICT';
}
