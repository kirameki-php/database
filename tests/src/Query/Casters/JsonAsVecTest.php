<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Casters;

use Kirameki\Core\Exceptions\TypeConversionException;
use Kirameki\Database\Query\Casters\JsonAsVec;
use Tests\Kirameki\Database\Query\QueryTestCase;

class JsonAsVecTest extends QueryTestCase
{
    public function test_cast_valid_json(): void
    {
        $caster = new JsonAsVec();
        $this->assertSame(['a', 'b'], $caster->cast('["a","b"]')->all());
    }

    public function test_cast_empty_list_json(): void
    {
        $caster = new JsonAsVec();
        $this->assertSame([], $caster->cast('[]')->all());
    }

    public function test_cast_invalid_json(): void
    {
        $this->expectException(TypeConversionException::class);
        $this->expectExceptionMessage('Expected: valid JSON. Got: array.');
        $caster = new JsonAsVec();
        $caster->cast([]);
    }

    public function test_cast_non_list_json(): void
    {
        $this->expectException(TypeConversionException::class);
        $this->expectExceptionMessage('Expected: list of values. Got: {"a":1}.');
        $caster = new JsonAsVec();
        $caster->cast('{"a":1}');
    }

    public function test_cast_null(): void
    {
        $caster = new JsonAsVec();
        $this->assertNull($caster->cast(null));
    }
}
