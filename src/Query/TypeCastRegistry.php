<?php declare(strict_types=1);

namespace Kirameki\Database\Query;

use BackedEnum;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\Query\Casters\EnumCaster;
use Kirameki\Database\Query\Casters\TimeCaster;
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
            $type === Time::class => new TimeCaster(),
            is_a($type, BackedEnum::class, true) => new EnumCaster($type),
            is_a($type, TypeCaster::class, true) => new $type(),
            default => throw new LogicException("No caster found for type: $type", [
                'type' => $type,
            ]),
        };
    }
}
