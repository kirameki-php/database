<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Collections\Utils\Arr;
use Kirameki\Database\Query\Syntax\QuerySyntax;
use Override;

class RawStatement extends QueryStatement
{
    /**
     * @param string $template
     * @param iterable<int, mixed> $parameters
     * @param array<string, string>|null $casts
     * @param Tags|null $tags
     */
    public function __construct(
        public readonly string $template,
        public readonly iterable $parameters = [],
        array|null $casts = null,
        ?Tags $tags = null,
    )
    {
        parent::__construct($casts, $tags);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function generateTemplate(QuerySyntax $syntax): string
    {
        return $syntax->prepareTemplateForRaw($this);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function generateParameters(QuerySyntax $syntax): array
    {
        return $syntax->prepareParametersForRaw($this);
    }
}
