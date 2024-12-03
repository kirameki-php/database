<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Casters;

use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Time\Time;
use Override;
use function is_float;
use function is_int;
use function is_string;

final class TimeCaster implements TypeCaster
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function cast(mixed $value): Time
    {
        if (is_string($value)) {
            return new Time($value);
        }

        if (is_int($value) || is_float($value)) {
            return Time::createFromTimestamp($value);
        }

        throw new LogicException('Invalid time value type. Expected string, int or float.', [
            'value' => $value,
        ]);
    }
}
