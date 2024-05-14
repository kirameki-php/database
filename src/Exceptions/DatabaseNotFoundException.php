<?php declare(strict_types=1);

namespace Kirameki\Database\Exceptions;

use Kirameki\Core\Exceptions\RuntimeException;
use Kirameki\Database\Adapters\ConnectionConfig;

class DatabaseNotFoundException extends RuntimeException
{
    public function __construct(string $name, ConnectionConfig $config)
    {
        parent::__construct("'$name' does not exist.");
        $this->addContext('config', $config);
    }
}
