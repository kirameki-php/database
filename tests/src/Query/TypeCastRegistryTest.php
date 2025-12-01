<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query;

use Kirameki\Collections\Utils\Arr;
use Kirameki\Exceptions\LogicException;
use Kirameki\Database\Query\Casters\DynamicEnumCaster;
use Kirameki\Database\Query\Casters\JsonToVec;
use Kirameki\Database\Query\Casters\StringToTime;
use Kirameki\Database\Query\TypeCastRegistry;
use Kirameki\Time\Time;
use Tests\Kirameki\Database\Query\Casters\_Support\IntCastEnum;

class TypeCastRegistryTest extends QueryTestCase
{
    public function test_getCaster_with_default_cast_enum(): void
    {
        $registry = new TypeCastRegistry();
        $caster = $registry->getCaster(IntCastEnum::class);
        $this->assertInstanceOf(DynamicEnumCaster::class, $caster);
    }

    public function test_getCaster_with_default_cast_time(): void
    {
        $registry = new TypeCastRegistry();
        $caster = $registry->getCaster(Time::class);
        $this->assertInstanceOf(StringToTime::class, $caster);
    }

    public function test_getCaster_with_TypeCaster_class(): void
    {
        $registry = new TypeCastRegistry();
        $caster = $registry->getCaster(JsonToVec::class);
        $this->assertInstanceOf(JsonToVec::class, $caster);
    }

    public function test_getCaster_with_fail_on_invalid_class(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No caster found for type: Kirameki\Collections\Utils\Arr');
        $registry = new TypeCastRegistry();
        $registry->getCaster(Arr::class);
    }
}
