<?php

declare(strict_types=1);

namespace App\Entity;

use JsonSerializable;
use ReflectionClassConstant;
use ReflectionObject;

abstract class AbstractStationConfiguration implements JsonSerializable
{
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

    public function jsonSerialize(): array
    {
        return $this->toArray();
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
