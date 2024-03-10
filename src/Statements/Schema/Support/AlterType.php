<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema\Support;

enum AlterType: string
{
    case Add = 'ADD';
    case Modify = 'MODIFY';
}
