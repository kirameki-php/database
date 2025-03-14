<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Syntax\QuerySyntax;
use function dump;

abstract class QueryStatement
{
    /**
     * @param CteAggregate|null $with
     * @param array<string, string>|null $casts
     * @param Tags|null $tags
     */
    public function __construct(
        public ?CteAggregate $with = null,
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
