<?php

declare(strict_types=1);

namespace App\Entity\Api;

use JsonSerializable;

/**
 * A utility class ensuring that JSON will correctly represent
 * associative arrays (hashmaps) in responses as objects,
 * even when empty.
 */
final readonly class HashMap implements JsonSerializable
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
}
