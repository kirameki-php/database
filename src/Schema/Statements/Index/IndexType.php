<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements\Index;

enum IndexType: string
{
    case Default = '';
    case Unique = 'UNIQUE';
}
