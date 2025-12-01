<?php

declare(strict_types=1);

namespace Kirameki\Database\Query\Casters;

use Kirameki\Collections\Vec;
use Kirameki\Exceptions\TypeConversionException;
use Kirameki\Core\Json;
use function array_is_list;
use function is_array;

class JsonToVec implements TypeCaster
{
    /**
     * @inheritDoc
     * @return Vec<mixed>|null
     */
    public function cast(mixed $value): ?Vec
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            throw new TypeConversionException('Expected: valid JSON. Got: ' . gettype($value) . '.', [
                'value' => $value,
            ]);
        }

        $array = Json::decode($value);

        if (is_array($array) && array_is_list($array)) {
            return new Vec($array);
        }

        throw new TypeConversionException('Expected: list of values. Got: ' . $value . '.', [
            'value' => $value,
        ]);
    }
}
