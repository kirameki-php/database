<?php declare(strict_types=1);

namespace Kirameki\Database\Query;

use BackedEnum;
use Kirameki\Exceptions\LogicException;
use Kirameki\Database\Query\Casters\DynamicEnumCaster;
use Kirameki\Database\Query\Casters\StringToTime;
use Kirameki\Database\Query\Casters\TypeCaster;
use Kirameki\Time\Time;
use function is_a;

class TypeCastRegistry
{
    /**
     * @param array<string, TypeCaster> $casters
     */
    public function __construct(
        protected array $casters = [],
    )
    {
    }

    /**
     * @param string $type
     * @return TypeCaster
     */
    public function getCaster(string $type): TypeCaster
    {
        return $this->casters[$type] ??= $this->resolve($type);
    }

    /**
     * @param string $type
     * @return TypeCaster
     */
    protected function resolve(string $type): TypeCaster
    {
        return match (true) {
            $type === Time::class => new StringToTime(),
            is_a($type, BackedEnum::class, true) => new DynamicEnumCaster($type),
            is_a($type, TypeCaster::class, true) => new $type(),
            default => throw new LogicException("No caster found for type: $type", [
                'type' => $type,
            ]),
        };
    }
}
