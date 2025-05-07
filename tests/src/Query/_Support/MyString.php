<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\_Support;

use Stringable;

class MyString implements Stringable
{
    public function __construct(
        protected string $value,
    ) {
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
