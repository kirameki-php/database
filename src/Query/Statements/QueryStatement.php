<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Syntax\QuerySyntax;

abstract class QueryStatement
{
    /**
     * @param list<With>|null $with
     * @param array<string, string>|null $casts
     * @param Tags|null $tags
     */
    public function __construct(
        public ?array $with = null,
        public ?array $casts = null,
        public ?Tags $tags = null,
    )
    {
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
