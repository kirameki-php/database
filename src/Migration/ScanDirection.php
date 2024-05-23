<?php declare(strict_types=1);

namespace Kirameki\Database\Migration;

enum ScanDirection: string
{
    case Forward = 'forward';
    case Backward = 'backward';
}
