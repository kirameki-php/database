<?php declare(strict_types=1);

namespace Kirameki\Database\Statements;

class RawStatement implements Statement
{

    /**
     * @param Syntax $syntax
     * @param string $raw
     * @param array<mixed> $parameters
     */
    public function __construct(
        protected readonly Syntax $syntax,
        protected readonly string $raw,
        protected readonly array $parameters = [],
    )
    {
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
