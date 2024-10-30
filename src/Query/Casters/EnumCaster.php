<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Casters;

use BackedEnum;
use Override;

/**
 * @template T of BackedEnum
 */
final class EnumCaster implements TypeCaster
{
    /**
     * @param class-string<T> $class
     */
    public function __construct(
        public readonly string $class,
    )
    {
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function cast(mixed $value): BackedEnum
    {
        return $this->class::from($value);
    }
}
