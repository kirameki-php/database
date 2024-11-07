<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Syntax\QuerySyntax;
use stdClass;

interface Normalizable
{
    /**
     * @param QuerySyntax $syntax
     * @param stdClass $row
     * @return stdClass
     */
    public function normalize(QuerySyntax $syntax, stdClass $row): stdClass;
}
