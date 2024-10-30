<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Support\Tags;
use Kirameki\Database\Query\Syntax\QuerySyntax;
use Override;
use function iterator_to_array;

class RawStatement extends QueryStatement
{
    /**
     * @param array<string, string>|null $casts
     * @param Tags|null $tags
     * @param string $template
     * @param iterable<int, mixed> $parameters
     */
    public function __construct(
        array|null $casts,
        ?Tags $tags,
        public readonly string $template,
        public readonly iterable $parameters = [],
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
        return $this->template;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function generateParameters(QuerySyntax $syntax): array
    {
        return iterator_to_array($this->parameters);
    }
}
