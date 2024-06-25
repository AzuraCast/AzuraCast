<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Inflector\Inflector;
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

    protected readonly Inflector $inflector;

    /**
     * @param ConfigData $data
     */
    public function __construct(
        array $data = []
    ) {
        $this->inflector = InflectorFactory::create()->build();
        $this->fromArray($data);
    }

    /**
     * @param ConfigData|AbstractStationConfiguration $data
     * @return $this
     */
    public function fromArray(
        array|self $data
    ): static {
        if ($data instanceof self) {
            $data = $data->toArray();
        }

        foreach ($data as $dataKey => $dataVal) {
            $methodName = $this->inflector->camelize('set_' . $dataKey);
            if (method_exists($this, $methodName)) {
                $this->$methodName($dataVal);
            } else {
                $this->set($dataKey, $dataVal);
            }
        }

        return $this;
    }

    /**
     * @return ConfigData
     */
    public function toArray(): array
    {
        $return = [];
        $reflClass = new ReflectionObject($this);

        foreach ($reflClass->getConstants(ReflectionClassConstant::IS_PUBLIC) as $dataKey) {
            $getMethodName = $this->inflector->camelize('get_' . $dataKey);
            $methodName = $this->inflector->camelize($dataKey);

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
