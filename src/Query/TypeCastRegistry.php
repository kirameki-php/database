<?php declare(strict_types=1);

namespace Kirameki\Database\Query;

use BackedEnum;
use Closure;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\Query\Casters\EnumCaster;
use Kirameki\Database\Query\Casters\TimeCaster;
use Kirameki\Database\Query\Casters\TypeCaster;
use Kirameki\Time\Time;
use function array_key_exists;
use function is_a;

class TypeCastRegistry
{
    /**
     * @param array<string, TypeCaster> $casters
     * @param array<string, Closure(): TypeCaster> $resolvers
     */
    public function __construct(
        protected array $casters = [],
        protected array $resolvers = [],
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
     * @param Closure(): TypeCaster $resolver
     */
    public function addResolver(string $type, Closure $resolver): void
    {
        $this->resolvers[$type] = $resolver;
    }

    /**
     * @param string $type
     * @return TypeCaster
     */
    protected function resolve(string $type): TypeCaster
    {
        if (array_key_exists($type, $this->resolvers)) {
            return $this->resolvers[$type]();
        }

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
