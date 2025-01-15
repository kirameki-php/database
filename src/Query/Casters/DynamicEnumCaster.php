<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Casters;

use BackedEnum;
use Override;

/**
 * @template TEnumClass of BackedEnum
 */
final class DynamicEnumCaster implements TypeCaster
{
    /**
     * @param class-string<TEnumClass> $class
     */
    public function __construct(
        public readonly string $class,
    )
    {
    }

    /**
     * @inheritDoc
     * @return TEnumClass|null
     */
    #[Override]
    public function cast(mixed $value): ?BackedEnum
    {
        if ($value === null) {
            return null;
        }
        return $this->class::from($value);
    }
}
