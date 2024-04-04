<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Syntax\QuerySyntax;
use Override;

class RawStatement extends QueryStatement
{

    /**
     * @param QuerySyntax $syntax
     * @param string $raw
     * @param array<mixed> $parameters
     */
    public function __construct(
        QuerySyntax $syntax,
        protected readonly string $raw,
        protected readonly array $parameters = [],
    )
    {
        parent::__construct($syntax);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function prepare(): Executable
    {
        return new Executable($this->raw);
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
