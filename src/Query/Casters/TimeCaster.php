<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Casters;

use Kirameki\Time\Time;
use Override;

final class TimeCaster implements TypeCaster
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function cast(mixed $value): Time
    {
        return new Time((string) $value);
    }
}
