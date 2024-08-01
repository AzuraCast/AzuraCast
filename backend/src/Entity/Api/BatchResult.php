<?php

declare(strict_types=1);

namespace App\Entity\Api;

use JsonSerializable;

abstract class BatchResult implements JsonSerializable
{
    public array $errors = [];

    /**
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        return [
            'success' => empty($this->errors),
            'errors' => $this->errors,
        ];
    }
}
