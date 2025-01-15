<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Casters;

use Error;
use Kirameki\Database\Query\Casters\DynamicEnumCaster;
use stdClass;
use Tests\Kirameki\Database\Query\Casters\_Support\IntCastEnum;
use Tests\Kirameki\Database\Query\Casters\_Support\StringCastEnum;
use Tests\Kirameki\Database\Query\QueryTestCase;

class DynamicEnumCasterTest extends QueryTestCase
{
    public function test_cast_from_int(): void
    {
        $caster = new DynamicEnumCaster(IntCastEnum::class);
        $this->assertSame(IntCastEnum::A, $caster->cast(1));
    }

    public function test_cast_from_string(): void
    {
        $caster = new DynamicEnumCaster(StringCastEnum::class);
        $this->assertSame(StringCastEnum::A, $caster->cast('a'));
    }

    public function test_cast_null(): void
    {
        $caster = new DynamicEnumCaster(IntCastEnum::class);
        $this->assertNull($caster->cast(null));
    }

    public function test_cast_non_enum_class(): void
    {
        $this->expectException(Error::class);
        $this->expectExceptionMessage('Call to undefined method stdClass::from()');

        $caster = new DynamicEnumCaster(stdClass::class);
        $caster->cast(1);
    }
}
