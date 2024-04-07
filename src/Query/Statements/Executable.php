<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

class Executable
{
    /**
     * @param string $template
     * @param iterable<int, mixed> $parameters
     */
    public function __construct(
        public string $template,
        public iterable $parameters = [],
    )
    {
    }
}
