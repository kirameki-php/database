<?php declare(strict_types=1);

namespace Kirameki\Database\Exceptions;

class DatabaseExistsException extends DatabaseException
{
    public function __construct(string $name, ?iterable $context = null)
    {
        parent::__construct("'$name' already exists.", $context);
    }
}
