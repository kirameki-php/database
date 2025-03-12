<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

class ConditionBuilder
{
    /**
     * @var array<array{logic: Logic, args: array<mixed>}>
     */
    public protected(set) array $entries = [];

    /**
     * @param mixed ...$args
     */
    public function __construct(mixed ...$args)
    {
        if (count($args) > 0) {
            $this->and(...$args);
        }
    }

    /**
     * @param mixed ...$args
     * @return $this
     */
    public function __invoke(mixed ...$args): static
    {
        return $this->and(...$args);
    }

    /**
     * @param mixed ...$args
     * @return $this
     */
    public function where(mixed ...$args): static
    {
        return $this->and(...$args);
    }

    /**
     * @param mixed ...$args
     * @return $this
     */
    public function and(mixed ...$args): static
    {
        return $this->addArgs(Logic::And, $args);
    }

    /**
     * @param mixed ...$args
     * @return $this
     */
    public function or(mixed ...$args): static
    {
        return $this->addArgs(Logic::Or, $args);
    }

    /**
     * @param Logic $logic
     * @param array<mixed> $args
     * @return $this
     */
    protected function addArgs(Logic $logic, array $args): static
    {
        $this->entries[] = [
            'logic' => $logic,
            'args' => $args,
        ];
        return $this;
    }
}
