<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Closure;
use Kirameki\Database\Query\QueryResult;
use Kirameki\Database\Query\Syntax\QuerySyntax;

abstract class QueryStatement
{
    /**
     * @param CteAggregate|null $with
     * @param array<string, string>|null $casts
     * @param Tags|null $tags
     * @param list<Closure(QueryResult<static, mixed>): mixed>|null $afterQuery
     */
    public function __construct(
        public ?CteAggregate $with = null,
        public ?array $casts = null,
        public ?Tags $tags = null,
        public ?array $afterQuery = null,
    )
    {
    }

    public function __clone(): void
    {
        if ($this->with !== null) {
            $this->with = clone $this->with;
        }
        if ($this->tags !== null) {
            $this->tags = clone $this->tags;
        }
    }

    /**
     * @param QuerySyntax $syntax
     * @return string
     */
    abstract public function generateTemplate(QuerySyntax $syntax): string;

    /**
     * @param QuerySyntax $syntax
     * @return list<mixed>
     */
    abstract public function generateParameters(QuerySyntax $syntax): array;

    /**
     * @param QuerySyntax $syntax
     * @return string
     */
    public function toSql(QuerySyntax $syntax): string
    {
        return $syntax->interpolate(
            $this->generateTemplate($syntax),
            $this->generateParameters($syntax),
        );
    }
}
