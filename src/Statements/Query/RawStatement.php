<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Query;

use Kirameki\Database\Statements\Query\Syntax\QuerySyntax;
use Kirameki\Database\Statements\Syntax;

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
    public function prepare(): string
    {
        return $this->raw;
    }

    /**
     * @inheritDoc
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @inheritDoc
     */
    public function toString(): string
    {
        return $this->syntax->interpolate($this);
    }
}
