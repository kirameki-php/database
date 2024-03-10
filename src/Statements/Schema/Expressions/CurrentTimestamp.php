<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema\Expressions;

class CurrentTimestamp
{
    /**
     * @var self
     */
    protected static self $instance;

    /**
     * @return self
     */
    public static function instance(): self
    {
        return self::$instance ??= new self();
    }
}
