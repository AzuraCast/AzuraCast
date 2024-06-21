<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Inflector\InflectorFactory;
use JsonSerializable;
use ReflectionClassConstant;
use ReflectionObject;

/**
 * @phpstan-type ConfigData array<string, mixed>
 */
abstract class AbstractStationConfiguration implements JsonSerializable
{
    /** @var ConfigData */
    protected array $data = [];

    /** @var bool Whether this container can hold arbitrary values that aren't configured. */
    protected bool $unrestricted = false;

    /**
     * @param ConfigData $data
     */
    public function __construct(
        array $data = []
    ) {
        $this->fromArray($data);
    }

    public function fromArray(
        array|self $data,
        bool $forceOverwrite = false
    ): static {
        if ($data instanceof self) {
            $data = $data->toArray();
        }

        if ($forceOverwrite) {
            $this->data = [];
        }

        $inflector = InflectorFactory::create()->build();

        foreach ($data as $dataKey => $dataVal) {
            $methodName = $inflector->camelize('set_' . $dataKey);
            if (method_exists($this, $methodName)) {
                $this->$methodName($dataVal);
            } else {
                $this->set($dataKey, $dataVal);
            }
        }

        return $this;
    }

    public function toArray(): array
    {
        if ($this->unrestricted) {
            $keys = array_keys($this->data);
        } else {
            $reflClass = new ReflectionObject($this);
            $keys = $reflClass->getConstants(ReflectionClassConstant::IS_PUBLIC);
        }

        $inflector = InflectorFactory::create()->build();

        $return = [];
        foreach ($keys as $dataKey) {
            $getMethodName = $inflector->camelize('get_' . $dataKey);
            $methodName = $inflector->camelize($dataKey);

            $return[$dataKey] = match (true) {
                method_exists($this, $getMethodName) => $this->$getMethodName(),
                method_exists($this, $methodName) => $this->$methodName(),
                default => $this->get($dataKey)
            };
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

    protected function set(string $key, mixed $value): static
    {
        $this->data[$key] = $value;
        return $this;
    }
}
