<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Casters;

use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\Query\Casters\StringAsTime;
use Kirameki\Time\Time;
use Tests\Kirameki\Database\Query\QueryTestCase;
use function time;

class StringAsTimeTest extends QueryTestCase
{
    public function test_cast_string(): void
    {
        $caster = new StringAsTime();
        $timeAsString = '2025-12-31 12:34:56.123456';
        $this->assertSame($timeAsString, $caster->cast($timeAsString)?->format('Y-m-d H:i:s.u'));
    }

    public function test_cast_invalid_type(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Invalid time value type. Expected string.');
        $caster = new StringAsTime();
        $caster->cast([]);
    }

    public function test_cast_null(): void
    {
        $caster = new StringAsTime();
        $this->assertNull($caster->cast(null));
    }
}
