<?php declare(strict_types=1);

namespace Kirameki\Database\Exceptions;

class DatabaseNotFoundException extends DatabaseException
{
    public function __construct(string $name, ?iterable $context = null)
    {
        parent::__construct("'$name' does not exist.", $context);
    }
}
