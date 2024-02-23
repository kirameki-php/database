<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

class AlterRenameColumnAction
{
    public string $from;

    public string $to;

    public function __construct(string $from, string $to)
    {
        $this->from = $from;
        $this->to = $to;
    }
}
