<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\_Support;

enum StatusEnum: int
{
    case Active = 1;
    case Inactive = 0;
}
