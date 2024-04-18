<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

use Kirameki\Database\Schema\Syntax\SchemaSyntax;
use Override;

class RawStatement extends SchemaStatement
{
    /**
     * @param string $command
     */
    public function __construct(
        protected string $command,
    )
    {
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function toExecutable(SchemaSyntax $syntax): array
    {
        return [$this->command];
    }
}
