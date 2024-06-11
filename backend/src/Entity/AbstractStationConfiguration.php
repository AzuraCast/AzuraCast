<?php

declare(strict_types=1);

namespace App\Entity;

use JsonSerializable;
use ReflectionClassConstant;
use ReflectionObject;

/**
 * @phpstan-type ConfigData array<string, mixed>
 */
abstract class AbstractStationConfiguration implements JsonSerializable
{
    /**
     * @param ConfigData $data
     */
    public function __construct(
        private array $data = []
    ) {
    }

    public function toArray(): array
    {
        $reflClass = new ReflectionObject($this);

        $return = [];
        foreach ($reflClass->getConstants(ReflectionClassConstant::IS_PUBLIC) as $constantVal) {
            $constantResult = $this->get($constantVal);
            if (null !== $constantResult) {
                $return[(string)$constantVal] = $constantResult;
            }
        }
        return $return;
    }

    public function jsonSerialize(): array|object
    {
        $result = $this->toArray();

        return (0 !== count($result))
            ? $result
            : (object)[];
    }

    protected function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    protected function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }
}
