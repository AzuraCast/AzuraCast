<?php

declare(strict_types=1);

namespace App\Entity\Api\Form;

use JsonSerializable;

abstract readonly class AbstractOptions implements JsonSerializable
{
    public function __construct(
        protected array $data
    ) {
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function jsonSerialize(): array
    {
        return $this->data;
    }
}
