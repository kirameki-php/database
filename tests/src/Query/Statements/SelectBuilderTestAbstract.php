<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Statements\SelectStatement;
use Tests\Kirameki\Database\Query\QueryTestCase;

abstract class SelectBuilderTestAbstract extends QueryTestCase
{
    abstract public function test_plain(): void;

    abstract public function test_from(): void;

    abstract public function test_from__with_alias(): void;

    abstract public function test_from__multiple_tables(): void;

    abstract public function test_from__with_multiple_where_column(): void;

    abstract public function test_from__with_expression(): void;

    abstract public function test_columns(): void;

    abstract public function test_columns__with_alias(): void;

    abstract public function test_columns__with_alias_embedded(): void;

    abstract public function test_distinct(): void;

    abstract public function test_forceIndex(): void;

    abstract public function test_join_using_on(): void;

    abstract public function test_join_using_on_and_where(): void;

    abstract public function test_joinOn(): void;

    abstract public function test_lockForUpdate(): void;

    abstract public function test_lockForUpdate_with_option_nowait(): void;

    abstract public function test_lockForUpdate_with_option_skip_locked(): void;

    abstract public function test_lockForShare(): void;

    abstract public function test_where__with_two_args(): void;

    abstract public function test_where__with_two_args_named_operator_ne(): void;

    abstract public function test_where__multiples(): void;

    abstract public function test_where__combined(): void;

    abstract public function test_where__with_nested_nesting(): void;

    abstract public function test_whereColumn(): void;

    abstract public function test_whereColumn__aliased(): void;

    abstract public function test_where__tuple(): void;

    abstract public function test_where__and__from_two_wheres(): void;

    abstract public function test_where__or(): void;

    abstract public function test_where__and_plus_or(): void;

    abstract public function test_where__and__with_nested_or(): void;

    abstract public function test_orderBy(): void;

    abstract public function test_orderByAsc(): void;

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

    abstract public function test_cursor(): void;

    abstract public function test_exactly__matches(): void;

    abstract public function test_exactly__does_not_match(): void;

    abstract public function test_offsetPaginate(): void;

    abstract public function test_offsetPaginate__with_invalid_page(): void;

    abstract public function test_offsetPaginate__with_invalid_size(): void;

    abstract public function test_cursorPaginate(): void;

    abstract public function test_cursorPaginate__with__invalid_size(): void;

    abstract public function test_first(): void;

    abstract public function test_firstOrNull(): void;

    abstract public function test_single(): void;

    abstract public function test_single__empty(): void;

    abstract public function test_single__multiple_rows(): void;

    abstract public function test_pluck(): void;

    abstract public function test_value(): void;

    abstract public function test_value__empty(): void;

    abstract public function test_value__unknown_column(): void;

    abstract public function test_valueOrNull(): void;

    abstract public function test_valueOrNull__empty(): void;

    abstract public function test_valueOrNull__unknown_column(): void;

    abstract public function test_exists__returns_true(): void;

    abstract public function test_exists__returns_false(): void;

    abstract public function test_count__nothing(): void;

    abstract public function test_count__some(): void;

    abstract public function test_count__with_groupBy_throws_error(): void;

    abstract public function test_tally(): void;

    abstract public function test_tally__without_grouping(): void;

    abstract public function test_sum(): void;

    abstract public function test_avg(): void;

    abstract public function test_min(): void;

    abstract public function test_max(): void;

    abstract public function test_batch__without_limit(): void;

    abstract public function test_batch__with_limit_less_than_size(): void;

    abstract public function test_batch__with_limit_greater_than_size(): void;

    abstract public function test_flatBatch(): void;

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
}
