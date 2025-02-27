<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Statements\SelectStatement;
use Tests\Kirameki\Database\Query\QueryTestCase;

abstract class SelectBuilderTestAbstract extends QueryTestCase
{
    abstract public function test_plain(): void;

    abstract public function test_from(): void;

    abstract public function test_from__with_alias(): void;

    abstract public function test_from_multiple(): void;

    abstract public function test_from_multiple_where_column(): void;

    abstract public function test_from__with_expression(): void;

    abstract public function test_columns(): void;

    abstract public function test_columns_with_alias(): void;

    abstract public function test_distinct(): void;

    abstract public function test_join_using_on(): void;

    abstract public function test_join_using_on_and_where(): void;

    abstract public function test_joinOn(): void;

    abstract public function test_lockForUpdate(): void;

    abstract public function test_lockForUpdate_with_option_nowait(): void;

    abstract public function test_lockForUpdate_with_option_skip_locked(): void;

    abstract public function test_lockForShare(): void;

    abstract public function test_where_with_two_args(): void;

    abstract public function test_where_with_three_args(): void;

    abstract public function test_where_multiples(): void;

    abstract public function test_where_combined(): void;

    abstract public function test_where_column(): void;

    abstract public function test_where_column_aliased(): void;

    abstract public function test_where_tuple(): void;

    abstract public function test_orderBy(): void;

    abstract public function test_orderByDesc(): void;

    abstract public function test_groupBy(): void;

    abstract public function test_reorder(): void;

    abstract public function test_where_and_limit(): void;

    abstract public function test_where_and_offset(): void;

    abstract public function test_combination(): void;

    abstract public function test_compound_union(): void;

    abstract public function test_compound_union_all(): void;

    abstract public function test_compound_intersect(): void;

    abstract public function test_compound_except(): void;

    abstract public function test_compound_orderBy(): void;

    abstract public function test_compound_orderByAsc(): void;

    abstract public function test_compound_orderByDesc(): void;

    abstract public function test_compound_reorder(): void;

    abstract public function test_compound_limit(): void;

    abstract public function test_clone(): void;

    public function test_getStatement(): void
    {
        $query = $this->selectBuilder()->from('User')->where('id', 1);
        $this->assertInstanceOf(SelectStatement::class, $query->statement);
    }

    abstract public function test_setTag(): void;

    abstract public function test_withTags(): void;

    public function test_execute(): void
    {
        $conn = $this->createTempConnection($this->useConnection);
        $table = $conn->schema()->createTable('t');
        $table->id();
        $table->execute();
        $result = $conn->query()->select()->from('t')->where('id', 1)->execute();
        $this->assertSame([], $result->all());
    }

    abstract public function test_explain(): void;

    abstract public function test_cast_to_time_from_string(): void;

    abstract public function test_cast_to_int_backed_enum(): void;

    abstract public function test_casts_to_different_casts(): void;
}
