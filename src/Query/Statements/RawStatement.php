<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Support\Tags;
use Kirameki\Database\Query\Syntax\QuerySyntax;
use Override;
use function iterator_to_array;

class RawStatement extends QueryStatement
{
    /**
     * @param QuerySyntax $syntax
     * @param Tags|null $tags
     * @param string $template
     * @param iterable<int, mixed> $parameters
     */
    public function __construct(
        QuerySyntax $syntax,
        ?Tags $tags,
        public readonly string $template,
        public readonly iterable $parameters = [],
    )
    {
        parent::__construct($syntax, $tags);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function prepare(): QueryExecutable
    {
        return $this->syntax->toExecutable(
            $this,
            $this->template,
            iterator_to_array($this->parameters),
        );
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function toString(): string
    {
        return $this->syntax->interpolate($this->prepare());
    }
}
