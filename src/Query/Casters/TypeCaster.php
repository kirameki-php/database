<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Casters;

interface TypeCaster
{
    /**
     * @param mixed $value
     * @return mixed
     */
    public function cast(mixed $value): mixed;
}
