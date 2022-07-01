<?php

declare(strict_types=1);

namespace App\Entity\Api;

use JsonSerializable;

final class BatchResult implements JsonSerializable
{
    public array $files = [];

    public array $directories = [];

    public array $errors = [];

    public ?array $responseRecord = null;

    /**
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        return [
            'success' => empty($this->errors),
            'files' => $this->files,
            'directories' => $this->directories,
            'errors' => $this->errors,
            'record' => $this->responseRecord,
        ];
    }
}
