<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Casters;

use Kirameki\Exceptions\LogicException;
use Kirameki\Time\Time;
use Override;
use function is_null;
use function is_string;

final class StringToTime implements TypeCaster
{
    /**
     * @inheritDoc
     * @return Time|null
     */
    #[Override]
    public function cast(mixed $value): ?Time
    {
        return match (true) {
            is_null($value) => null,
            is_string($value) => new Time($value),
            default => throw new LogicException('Invalid time value type. Expected string.', [
                'value' => $value,
            ]),
        };
    }
}
