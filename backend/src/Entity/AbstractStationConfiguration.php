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

    /**
     * @param array|AbstractStationConfiguration $data
     * @param bool $clearExisting Whether to clear existing data before setting new data.
     * @return $this
     */
    public function fromArray(
        array|self $data,
        bool $clearExisting = false
    ): static {
        if ($data instanceof self) {
            $data = $data->toArray();
        }

        if ($clearExisting) {
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

    /**
     * @param bool $callGetters Whether to run data through the relevant "get" functions if they exist.
     * @return ConfigData
     */
    public function toArray(bool $callGetters = false): array
    {
        if ($this->unrestricted) {
            if (!$callGetters) {
                return $this->data;
            }

            $keys = array_keys($this->data);
        } else {
            $reflClass = new ReflectionObject($this);
            $keys = $reflClass->getConstants(ReflectionClassConstant::IS_PUBLIC);
        }

        $return = [];
        if ($callGetters) {
            $inflector = InflectorFactory::create()->build();

            foreach ($keys as $dataKey) {
                $getMethodName = $inflector->camelize('get_' . $dataKey);
                $methodName = $inflector->camelize($dataKey);

                $return[$dataKey] = match (true) {
                    method_exists($this, $getMethodName) => $this->$getMethodName(),
                    method_exists($this, $methodName) => $this->$methodName(),
                    default => $this->get($dataKey)
                };
            }
        } else {
            foreach ($keys as $constantKey) {
                $constantResult = $this->get($constantKey);
                if (null !== $constantResult) {
                    $return[$constantKey] = $constantResult;
                }
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

    protected function set(string $key, mixed $value): static
    {
        $this->data[$key] = $value;
        return $this;
    }
}
