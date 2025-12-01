<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Casters;

use Kirameki\Exceptions\TypeConversionException;
use Kirameki\Database\Query\Casters\JsonToVec;
use Tests\Kirameki\Database\Query\QueryTestCase;

class JsonAsVecTest extends QueryTestCase
{
    public function test_cast_valid_json(): void
    {
        $caster = new JsonToVec();
        $this->assertSame(['a', 'b'], $caster->cast('["a","b"]')->all());
    }

    public function test_cast_empty_list_json(): void
    {
        $caster = new JsonToVec();
        $this->assertSame([], $caster->cast('[]')->all());
    }

    public function test_cast_invalid_json(): void
    {
        $this->expectException(TypeConversionException::class);
        $this->expectExceptionMessage('Expected: valid JSON. Got: array.');
        $caster = new JsonToVec();
        $caster->cast([]);
    }

    public function test_cast_non_list_json(): void
    {
        $this->expectException(TypeConversionException::class);
        $this->expectExceptionMessage('Expected: list of values. Got: {"a":1}.');
        $caster = new JsonToVec();
        $caster->cast('{"a":1}');
    }

    public function test_cast_null(): void
    {
        $caster = new JsonToVec();
        $this->assertNull($caster->cast(null));
    }
}
