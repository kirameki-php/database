<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements\Table;

enum AlterType: string
{
    case Add = 'ADD';
    case Modify = 'MODIFY';
}
