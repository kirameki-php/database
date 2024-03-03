<?php declare(strict_types=1);

namespace Kirameki\Database\Exceptions;

use Kirameki\Core\Exceptions\RuntimeException;
use Kirameki\Database\Adapters\DatabaseConfig;

class DatabaseNotFoundException extends RuntimeException
{
    public function __construct(string $name, DatabaseConfig $config)
    {
        parent::__construct("'$name' does not exist.");
        $this->addContext('config', $config);
    }
}
