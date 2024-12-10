<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Expressions;

use Kirameki\Database\Schema\Syntax\SchemaSyntax;
use Override;

class RawDefaultValue implements DefaultValue
{
    /**
     * @param string $value
     */
    public function __construct(
        public readonly string $value,
    )
    {
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function toString(SchemaSyntax $syntax): string
    {
        return $this->value;
    }
}
