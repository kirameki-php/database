<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Support;

use Kirameki\Database\Query\Statements\Tags;
use Tests\Kirameki\Database\Query\QueryTestCase;
use function iterator_to_array;

class TagsTest extends QueryTestCase
{
    public function test_add(): void
    {
        $tags = new Tags();
        $tags->add('k1', 'v1');
        $tags->add('k2', 'v2');
        $this->assertSame(['k1' => 'v1', 'k2' => 'v2'], iterator_to_array($tags));
    }

    public function test_merge(): void
    {
        $tags1 = new Tags(['k1' => 'v1']);
        $tags2 = new Tags(['k2' => 'v2']);
        $tags1->merge($tags2);
        $this->assertSame(['k1' => 'v1', 'k2' => 'v2'], iterator_to_array($tags1));
    }
}
