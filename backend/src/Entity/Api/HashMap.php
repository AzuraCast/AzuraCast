<?php

declare(strict_types=1);

namespace App\Entity\Api;

use ArrayAccess;
use JsonSerializable;
use LogicException;
use OpenApi\Attributes as OA;

/**
 * A utility class ensuring that JSON will correctly represent
 * associative arrays (hashmaps) in responses as objects,
 * even when empty.
 *
 * @implements ArrayAccess<array-key, mixed>
 */
#[OA\Schema(
    description: 'A hash-map array represented as an object.',
    type: 'object'
)]
final readonly class HashMap implements JsonSerializable, ArrayAccess
{
    public function __construct(
        private array $data = []
    ) {
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function jsonSerialize(): mixed
    {
        return (0 !== count($this->data))
            ? $this->data
            : (object)[];
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException('Data is read-only.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('Data is read-only.');
    }
}
