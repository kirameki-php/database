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
     * @return self
     */
    public static function column(string $column, string $path): self
    {
        return new self(new Column($column), $path);
    }

    /**
     * @param string $target
     * @param string $path
     * @return self
     */
    public static function raw(string $target, string $path): self
    {
        return new self($target, $path);
    }

    /**
     * @param string|Expression $target
     * @param string $path
     */
    private function __construct(
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
