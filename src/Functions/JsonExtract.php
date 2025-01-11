<?php declare(strict_types=1);

namespace Kirameki\Database\Functions;

use Kirameki\Database\Expression;
use Kirameki\Database\Query\Expressions\Column;
use Kirameki\Database\Syntax;
use Override;

final class JsonExtract implements Expression
{
    /**
     * @var string
     */
    public readonly string $path;

    /**
     * @param string $column
     * @param string $path
     * @param string|null $as
     * @return self
     */
    public static function column(string $column, string $path, ?string $as = null): self
    {
        return new self(new Column($column), $path, $as);
    }

    /**
     * @param string $target
     * @param string $path
     * @return self
     */
    public static function literal(string $target, string $path): self
    {
        return new self($target, $path);
    }

    /**
     * @param string|Expression $target
     * @param string $path
     * @param string|null $as
     */
    private function __construct(
        public readonly string|Expression $target,
        string $path,
        public readonly ?string $as = null,
    )
    {
        $this->path = str_starts_with($path, '$.') ? $path : '$.' . $path;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function toValue(Syntax $syntax): string
    {
        $target = $this->target instanceof Expression
            ? $this->target->toValue($syntax)
            : $syntax->asLiteral($this->target);

        return $syntax->formatJsonExtract($target, $this->path, $this->as);
    }
}
