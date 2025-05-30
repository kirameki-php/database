<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements\ForeignKey;

enum ReferenceOption: string
{
    case NoAction = 'NO ACTION';
    case SetNull = 'SET NULL';
    case SetDefault = 'SET DEFAULT';
    case Cascade = 'CASCADE';
    case Restrict = 'RESTRICT';
}
