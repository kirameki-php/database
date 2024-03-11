<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Query\Expressions;

use Kirameki\Database\Statements\Expression;
use Kirameki\Database\Statements\Query\Syntax\QuerySyntax;

class JsonExtract extends Expression
{
    /**
     * @var string
     */
    public readonly string $path;

    /**
     * @param string $column
     * @param string $path
     */
    public function __construct(
        public readonly string $column,
        string $path,
    )
    {
        $this->path = str_starts_with($path, '$.') ? $path : '$.'.$path;;
    }

    /**
     * @param QuerySyntax $syntax
     * @return string
     */
    public function prepare(QuerySyntax $syntax): string
    {
        return $syntax->formatJsonExtract($this->column, $this->path);
    }
}
