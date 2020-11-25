<?php

namespace App\Entity\Api;

class BatchResult implements \JsonSerializable
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
