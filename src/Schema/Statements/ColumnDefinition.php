<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

class ColumnDefinition
{
    public string $name;

    public ?string $type;

    public ?int $size;

    public ?int $scale;

    public ?bool $primaryKey;

    public ?bool $nullable;

    public ?bool $autoIncrement;

    public ?string $comment;

    public mixed $default;

    /**
     * @param string $name
     * @param string|null $type
     * @param int|null $size
     * @param int|null $scale
     */
    public function __construct(string $name, string $type = null, ?int $size = null, ?int $scale = null)
    {
        $this->name = $name;
        $this->type = $type;
        $this->size = $size;
        $this->scale = $scale;
        $this->primaryKey = null;
        $this->nullable = true;
        $this->autoIncrement = null;
        $this->comment = null;
        $this->default = null;
    }
}
