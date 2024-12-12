<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Expressions;

use Kirameki\Database\Expression;
use Kirameki\Database\Query\Syntax\QuerySyntax;
use Kirameki\Database\Syntax;
use Override;

/**
 * @consistent-constructor
 * @implements Expression<QuerySyntax>
 */
class JsonExtract implements Expression
{
    /**
     * @var string
     */
    public readonly string $path;

    /**
     * @param string $column
     * @param string $path
     * @return static
     */
    public static function column(string $column, string $path): static
    {
        return new static(new Column($column), $path);
    }

    /**
     * @param string $target
     * @param string $path
     * @return static
     */
    public static function raw(string $target, string $path): static
    {
        return new static($target, $path);
    }

    /**
     * @param string|Expression $target
     * @param string $path
     */
    protected function __construct(
        public readonly string|Expression $target,
        string $path,
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
        return $syntax->formatJsonExtract($this->target, $this->path);
    }
}
