<?php

declare(strict_types=1);

namespace App\Doctrine;

use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\Serializer\Normalizer\DenormalizableInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * @phpstan-consistent-constructor
 * @phpstan-type ConfigData array<string, mixed>
 */
abstract class AbstractArrayEntity implements JsonSerializable, DenormalizableInterface
{
    /** @var ConfigData */
    protected array $data = [];

    /**
     * @param ConfigData $data
     */
    public function __construct(
        array $data = []
    ) {
        $this->fromArray($data);
    }

    public function denormalize(
        DenormalizerInterface $denormalizer,
        array|string|int|float|bool $data,
        ?string $format = null,
        array $context = []
    ): void {
        if (is_array($data)) {
            $this->fromArray($data);
        }
    }

    /**
     * @param AbstractArrayEntity|ConfigData|array<array-key, mixed> $data
     * @return $this
     */
    public function fromArray(
        array|self $data
    ): static {
        if ($data instanceof self) {
            $data = $data->getData();
        }

        // Only accept hashmap-style data, not lists.
        if (0 === count($data) || array_is_list($data)) {
            return $this;
        }

        foreach ($data as $dataKey => $dataVal) {
            if (!is_string($dataKey)) {
                continue;
            }

            // Don't set "empty" keys.
            $dataKey = trim($dataKey);
            if (empty($dataKey)) {
                continue;
            }

            if (property_exists($this, $dataKey)) {
                $this->$dataKey = $dataVal;
            } else {
                $this->set($dataKey, $dataVal);
            }
        }

        return $this;
    }

    public function getData(): array
    {
        $data = $this->data;
        ksort($data);
        return $data;
    }

    /**
     * @return ConfigData|null
     */
    public function toArray(): ?array
    {
        $return = [];

        foreach (self::getFields() as $dataKey) {
            $return[$dataKey] = match (true) {
                property_exists($this, $dataKey) => $this->$dataKey,
                default => $this->get($dataKey)
            };
        }

        ksort($return);

        return (0 === count($return)) ? null : $return;
    }

    public function jsonSerialize(): array|object
    {
        $result = $this->toArray();
        return $result ?? (object)[];
    }

    protected function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    protected function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * @return string[] Valid property names.
     */
    public static function getFields(): array
    {
        $reflClass = new ReflectionClass(static::class);
        return array_map(
            fn(ReflectionProperty $reflProp) => $reflProp->getName(),
            $reflClass->getProperties(ReflectionProperty::IS_VIRTUAL)
        );
    }

    /**
     * @param ConfigData|null $sourceData
     * @param ConfigData|AbstractArrayEntity|null $newData
     * @return ConfigData|null
     */
    public static function merge(
        ?array $sourceData,
        array|self|null $newData
    ): array|null {
        $arrayEntity = new static((array)$sourceData);

        if ($newData !== null) {
            $arrayEntity->fromArray($newData);
        }

        return $arrayEntity->getData();
    }
}
