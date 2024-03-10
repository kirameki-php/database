<?php declare(strict_types=1);

namespace Kirameki\Database\Statements;

interface Statement
{
    /**
     * @return string
     */
    public function prepare(): string;

    /**
     * @return array<mixed>
     */
    public function getParameters(): array;

    /**
     * @return string
     */
    public function toString(): string;
}
