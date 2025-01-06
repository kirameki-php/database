<?php declare(strict_types=1);

namespace Kirameki\Database\Functions;

use Kirameki\Database\Expression;
use Kirameki\Database\Syntax;
use Override;

final class CurrentTimestamp implements Expression
{
    /**
     * @param int|null $size
     */
    public function __construct(
        protected ?int $size = null,
    )
    {
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function toValue(Syntax $syntax): string
    {
        return $syntax->formatCurrentTimestamp($this->size);
    }
}
