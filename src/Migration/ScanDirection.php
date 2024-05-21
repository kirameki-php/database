<?php declare(strict_types=1);

namespace Kirameki\Database\Migration;

enum ScanDirection: string
{
    case Up = 'up';
    case Down = 'down';
}
