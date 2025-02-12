<?php declare(strict_types=1);

namespace Kirameki\Database\Functions;

use Kirameki\Database\Expression;
use Kirameki\Database\Query\Expressions\Column;
use Kirameki\Database\Syntax;
use Override;
use function array_map;
use function array_values;

final class Coalesce implements Expression
{
    /**
     * @param string ...$columns
     * @return self
     */
    public static function columns(string ...$columns): self
    {
        $columns = array_map(static fn(string $column) => new Column($column), $columns);
        return new self(array_values($columns));
    }

    /**
     * @param string|Expression ...$values
     * @return self
     */
    public static function values(string|Expression ...$values): self
    {
        return new self(array_values($values));
    }

    /**
     * @param list<string|Expression> $values
     */
    public function __construct(
        public readonly array $values,
    )
    {
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function toValue(Syntax $syntax): string
    {
        return $syntax->formatCoalesce($this->values);
    }
}
