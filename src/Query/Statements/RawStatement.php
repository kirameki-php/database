<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Support\QueryTags;
use Kirameki\Database\Query\Syntax\QuerySyntax;
use Override;
use function iterator_to_array;

class RawStatement extends QueryStatement
{
    /**
     * @param QuerySyntax $syntax
     * @param string $template
     * @param iterable<int, mixed> $parameters
     * @param QueryTags|null $tags
     */
    public function __construct(
        QuerySyntax $syntax,
        public readonly string $template,
        public readonly iterable $parameters = [],
        ?QueryTags $tags = null,
    )
    {
        parent::__construct($syntax, $tags);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function prepare(): Executable
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
        return $this->syntax->interpolate($this);
    }
}
