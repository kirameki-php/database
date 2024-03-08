<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Builders;

class PrimaryKeyConstraint
{
    /**
     * @var array<string, string>
     */
    public array $columns = [];
}